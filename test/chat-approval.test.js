import test from 'node:test';
import assert from 'node:assert/strict';
import {mkdtempSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';

test('chat write tool waits for approval and cannot replay it',async()=>{
  process.env.DB_PATH=join(mkdtempSync(join(tmpdir(),'urumi-approval-')),'test.db');
  process.env.ADMIN_PASSWORD='test-admin-password';
  process.env.LLM_API_KEY='test-key';
  process.env.LLM_BASE_URL='https://model.example/v1';
  process.env.LLM_MODEL='test-model';
  const db=await import('../src/db.js');
  db.saveServer('woocommerce','http://woocommerce-mcp.default.svc.cluster.local:3000/mcp',null,1);
  const {app}=await import('../src/gateway.js');
  const originalFetch=globalThis.fetch;
  let writeCalls=0;
  globalThis.fetch=async(url,options)=>{
    const target=String(url);
    if(target.startsWith('https://model.example/')){
      const body=JSON.parse(options.body);
      const hasToolResult=body.messages.some(m=>m.role==='tool');
      return new Response(JSON.stringify({choices:[{message:hasToolResult?{role:'assistant',content:'Product created.'}:{role:'assistant',content:null,tool_calls:[{id:'call-1',type:'function',function:{name:'woocommerce__create_product',arguments:JSON.stringify({name:'Blue Hoodie',price:'999'})}}]}}]}),{headers:{'content-type':'application/json'}});
    }
    if(target.includes('woocommerce-mcp.default.svc.cluster.local')){
      const body=JSON.parse(options.body);
      if(body.method==='tools/list')return new Response(JSON.stringify({jsonrpc:'2.0',id:body.id,result:{tools:[{name:'create_product',description:'Create product',inputSchema:{type:'object',properties:{name:{type:'string'},price:{type:'string'}}}}]}}),{headers:{'content-type':'application/json'}});
      if(body.method==='tools/call'){writeCalls++;return new Response(JSON.stringify({jsonrpc:'2.0',id:body.id,result:{content:[{type:'text',text:'{"id":42}'}]}}),{headers:{'content-type':'application/json'}})}
    }
    return originalFetch(url,options);
  };
  const server=app.listen(0,'127.0.0.1');
  await new Promise(resolve=>server.once('listening',resolve));
  const base=`http://127.0.0.1:${server.address().port}`;
  const post=async(path,body,key)=>originalFetch(base+path,{method:'POST',headers:{'content-type':'application/json',...(key?{authorization:`Bearer ${key}`}:{})},body:JSON.stringify(body)});
  const parse=text=>text.split('\n\n').filter(Boolean).map(block=>{const type=block.match(/^event: (.*)$/m)?.[1];const data=block.match(/^data: (.*)$/m)?.[1];return {type,data:data?JSON.parse(data):null}});
  try{
    const login=await (await post('/api/login',{username:'admin',password:'test-admin-password'})).json();
    const first=parse(await (await post('/api/chat/stream',{messages:[{role:'user',content:'Create a hoodie'}]},login.key)).text());
    const approval=first.find(e=>e.type==='approval')?.data;
    assert.ok(approval?.approvalId);
    assert.equal(approval.actions[0].arguments.name,'Blue Hoodie');
    assert.equal(writeCalls,0,'no write call before approval');
    const otherId=db.createUser('other','other-password','member');
    const otherKey=db.createKey(otherId,'test').key;
    const wrongUser=parse(await (await post('/api/chat/stream',{approvalId:approval.approvalId,decision:'approve'},otherKey)).text());
    assert.equal(wrongUser.find(e=>e.type==='error')?.data.message,'The request failed. Please try again or contact an admin.');
    assert.equal(writeCalls,0);
    const second=parse(await (await post('/api/chat/stream',{approvalId:approval.approvalId,decision:'approve'},login.key)).text());
    assert.equal(writeCalls,1);
    assert.equal(second.find(e=>e.type==='final')?.data.answer,'Product created.');
    const replay=parse(await (await post('/api/chat/stream',{approvalId:approval.approvalId,decision:'approve'},login.key)).text());
    assert.equal(replay.find(e=>e.type==='error')?.data.message.includes('already used'),true);
    assert.equal(writeCalls,1);
    const another=parse(await (await post('/api/chat/stream',{messages:[{role:'user',content:'Create another'}]},login.key)).text());
    const nextApproval=another.find(e=>e.type==='approval')?.data;
    const denied=parse(await (await post('/api/chat/stream',{approvalId:nextApproval.approvalId,decision:'reject'},login.key)).text());
    assert.equal(denied.find(e=>e.type==='final')?.data.answer,'Action cancelled. No write tool was called.');
    assert.equal(writeCalls,1);
    const memberServers=await (await originalFetch(`${base}/api/servers`,{headers:{authorization:`Bearer ${otherKey}`}})).json();
    const adminServers=await (await originalFetch(`${base}/api/servers`,{headers:{authorization:`Bearer ${login.key}`}})).json();
    assert.equal(memberServers[0].url,undefined,'members do not receive internal service URLs');
    assert.match(adminServers[0].url,/svc\.cluster\.local/);
    const memberProposal=parse(await (await post('/api/chat/stream',{messages:[{role:'user',content:'Create a hoodie'}]},otherKey)).text());
    const memberApproval=memberProposal.find(e=>e.type==='approval')?.data;
    const memberResult=parse(await (await post('/api/chat/stream',{approvalId:memberApproval.approvalId,decision:'approve'},otherKey)).text());
    const memberTool=memberResult.find(e=>e.type==='tool')?.data;
    assert.equal(memberTool.status,'success');
    assert.match(memberTool.requestId,/^[0-9a-f-]{36}$/);
    assert.ok(memberTool.auditId);
    assert.equal(memberTool.arguments,undefined,'member event omits raw arguments');
    assert.equal(memberTool.result,undefined,'member event omits raw results');
    const traceResponse=await originalFetch(`${base}/api/traces/${memberTool.requestId}`,{headers:{authorization:`Bearer ${otherKey}`}});
    assert.equal(traceResponse.status,200);
    const trace=await traceResponse.json();
    assert.equal(trace.username,'other');
    assert.equal(trace.id,memberTool.auditId);
    assert.equal(trace.request_id,memberTool.requestId);
    const auditEntries=await (await originalFetch(`${base}/api/audit`,{headers:{authorization:`Bearer ${login.key}`}})).json();
    assert.equal(auditEntries.find(item=>item.request_id===memberTool.requestId)?.id,memberTool.auditId);
    const outsiderId=db.createUser('outsider','outsider-password','member');
    const outsiderKey=db.createKey(outsiderId,'test').key;
    assert.equal((await originalFetch(`${base}/api/traces/${memberTool.requestId}`,{headers:{authorization:`Bearer ${outsiderKey}`}})).status,404,'members cannot inspect each other\'s traces');
    db.setEnabled('woocommerce',0);
    const hiddenServers=await (await originalFetch(`${base}/api/servers`,{headers:{authorization:`Bearer ${otherKey}`}})).json();
    const adminDisabledServers=await (await originalFetch(`${base}/api/servers`,{headers:{authorization:`Bearer ${login.key}`}})).json();
    assert.deepEqual(hiddenServers,[],'disabled integrations are absent for members');
    assert.equal(adminDisabledServers[0].enabled,false,'admins can still diagnose disabled integrations');
  }catch(error){process.stderr.write(`${error.stack}\n`);throw error}
  finally{globalThis.fetch=originalFetch;server.closeAllConnections();server.close()}
});
