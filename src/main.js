import {app} from './gateway.js';
import {saveServer,server} from './db.js';
const defaults = [
  ['woocommerce',process.env.WOO_MCP_URL],
  ['catalog',process.env.CATALOG_MCP_URL],
  ['clock',process.env.CLOCK_MCP_URL]
];
for (const [name,url] of defaults) if (url && !server(name)) saveServer(name,url,null,1);
app.listen(Number(process.env.PORT || 3000),'0.0.0.0',()=>console.log('Gateway listening'));
