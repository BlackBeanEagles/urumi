import express from 'express';
import {createHmac,randomBytes} from 'node:crypto';
import {mcpResponse,mcpError} from './mcp.js';

const kind = process.env.SERVER_KIND || 'woocommerce';
const app = express();
app.use(express.json());
const tool = (name,description,properties={},required=[]) => ({name,description,inputSchema:{type:'object',properties,required}});
const string = {type:'string'};
const tools = {
  woocommerce:[
    tool('list_products','List WooCommerce products',{per_page:{type:'integer'}}),
    tool('create_product','Create a simple published product',{name:string,price:string},['name','price']),
    tool('list_orders','List WooCommerce orders',{after:string,per_page:{type:'integer'}}),
    tool('get_order','Get an order by ID',{id:{type:'integer'}},['id']),
    tool('update_order_status','Update an order status',{id:{type:'integer'},status:string},['id','status'])
  ],
  catalog:[tool('store_info','Explain this store and its checkout',{topic:string})],
  clock:[tool('current_time','Get current time and date',{timezone:string})]
};
async function woo(path,method='GET',body) {
  const base=process.env.WOO_URL;
  const key=process.env.WOO_CONSUMER_KEY;
  const secret=process.env.WOO_CONSUMER_SECRET;
  if (!base || !key || !secret) throw new Error('WooCommerce configuration missing');
  const [route,query='']=path.split('?');
  const url=new URL(`${base.replace(/\/$/,'')}/`);
  url.searchParams.set('rest_route',`/wc/v3/${route}`);
  for (const [name,value] of new URLSearchParams(query)) url.searchParams.set(name,value);
  const headers={'content-type':'application/json'};
  if (url.protocol==='https:') {
    headers.authorization=`Basic ${Buffer.from(`${key}:${secret}`).toString('base64')}`;
  } else {
    // WooCommerce requires one-legged OAuth 1.0a over HTTP. Never send the secret in the URL.
    const index=await fetch(`${base.replace(/\/$/,'')}/?rest_route=/`,{signal:AbortSignal.timeout(8000)});
    if (!index.ok) throw new Error(`WooCommerce discovery HTTP ${index.status}`);
    const home=(await index.json()).home?.replace(/\/$/,'');
    if (!home) throw new Error('WooCommerce did not expose its home URL');
    const parameters=Object.fromEntries(url.searchParams);
    Object.assign(parameters,{
      oauth_consumer_key:key,
      oauth_timestamp:String(Math.floor(Date.now()/1000)),
      oauth_nonce:randomBytes(16).toString('hex'),
      oauth_signature_method:'HMAC-SHA256'
    });
    const encode=value=>encodeURIComponent(value).replace(/[!'()*]/g,char=>`%${char.charCodeAt(0).toString(16).toUpperCase()}`);
    const normalized=Object.keys(parameters).sort().map(name=>`${encode(name)}=${encode(parameters[name])}`).join('&');
    const signatureBase=`${method}&${encode(home)}&${encode(normalized)}`;
    parameters.oauth_signature=createHmac('sha256',`${secret}&`).update(signatureBase).digest('base64');
    for (const [name,value] of Object.entries(parameters)) url.searchParams.set(name,value);
  }
  const response=await fetch(url,{method,headers,body:body?JSON.stringify(body):undefined,signal:AbortSignal.timeout(8000)});
  const data=await response.json();
  if (!response.ok) throw new Error(data.message || `WooCommerce HTTP ${response.status}`);
  return data;
}
async function execute(name,args) {
  if (kind==='woocommerce') {
    switch(name) {
      case 'list_products': return woo(`products?per_page=${Math.min(args.per_page||20,100)}`);
      case 'create_product': return woo('products','POST',{name:args.name,regular_price:String(args.price),type:'simple',status:'publish'});
      case 'list_orders': return woo(`orders?per_page=${Math.min(args.per_page||20,100)}${args.after?`&after=${encodeURIComponent(args.after)}`:''}`);
      case 'get_order': return woo(`orders/${Number(args.id)}`);
      case 'update_order_status': return woo(`orders/${Number(args.id)}`,'PUT',{status:args.status});
    }
  }
  if (kind==='catalog' && name==='store_info') return {topic:args.topic||'general',answer:'This is a WooCommerce demonstration store. Checkout supports Cash on Delivery. Product and order details come from the WooCommerce tools.'};
  if (kind==='clock' && name==='current_time') return {now:new Date().toISOString(),timezone:args.timezone||'UTC'};
  throw new Error('Unknown tool');
}
app.get('/healthz',(_req,res)=>res.json({ok:true}));
app.post('/mcp',async(req,res)=>{
  const {id,method,params={}}=req.body;
  if(method==='notifications/initialized') return res.status(202).end();
  if(method==='initialize') return mcpResponse(res,id,{protocolVersion:'2025-03-26',capabilities:{tools:{}},serverInfo:{name:kind,version:'0.1.0'}});
  if(method==='ping') return mcpResponse(res,id,{});
  if(method==='tools/list') return mcpResponse(res,id,{tools:tools[kind]||[]});
  if(method==='tools/call') {
    try { const data=await execute(params.name,params.arguments||{}); return mcpResponse(res,id,{content:[{type:'text',text:JSON.stringify(data)}]}); }
    catch(e) { return mcpResponse(res,id,{isError:true,content:[{type:'text',text:e.message}]}); }
  }
  return mcpError(res,id,-32601,'Method not found');
});
app.listen(Number(process.env.PORT||3000),'0.0.0.0',()=>console.log(`${kind} MCP listening`));
