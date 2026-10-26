const headers = credential => ({ 'content-type':'application/json', 'accept':'application/json, text/event-stream', ...(credential ? {authorization:`Bearer ${credential}`} : {}) });
let seq = 0;
export async function rpc(url, credential, method, params = {}, timeout = 5000) {
  const response = await fetch(url, { method:'POST', headers:headers(credential), body:JSON.stringify({jsonrpc:'2.0',id:++seq,method,params}), signal:AbortSignal.timeout(timeout) });
  if (!response.ok) throw new Error(`Upstream HTTP ${response.status}`);
  const contentType = response.headers.get('content-type') || '';
  let message;
  if (contentType.includes('text/event-stream')) {
    const text = await response.text();
    const data = text.split('\n').filter(line => line.startsWith('data:')).map(line => line.slice(5).trim());
    message = JSON.parse(data.at(-1));
  } else message = await response.json();
  if (message.error) throw new Error(message.error.message || 'Upstream MCP error');
  return message.result;
}
export function mcpResponse(res, id, result) { res.set('content-type','application/json').json({jsonrpc:'2.0',id,result}); }
export function mcpError(res, id, code, message) { res.set('content-type','application/json').json({jsonrpc:'2.0',id,error:{code,message}}); }
export const capabilities = {protocolVersion:'2025-03-26',capabilities:{tools:{listChanged:true}},serverInfo:{name:'urumi-gateway',version:'0.1.0'}};
