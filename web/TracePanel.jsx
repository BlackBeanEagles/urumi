import React,{useEffect,useState} from 'react';

export default function TracePanel({requestId,keyToken,isAdmin,onClose,onNavigate,onLoaded}) {
  const [entry,setEntry]=useState(null);
  const [error,setError]=useState('');
  useEffect(()=>{
    const controller=new AbortController();
    setEntry(null);
    setError('');
    fetch(`/api/traces/${encodeURIComponent(requestId)}`,{headers:{authorization:`Bearer ${keyToken}`},signal:controller.signal})
      .then(async response=>{const data=await response.json();if(!response.ok)throw new Error(data.error||'Trace unavailable');return data})
      .then(data=>{setEntry(data);onLoaded?.()}).catch(err=>{if(err.name!=='AbortError')setError(err.message)});
    return()=>controller.abort();
  },[requestId,keyToken]);
  const failed=entry&&entry.outcome!=='success';
  const path=entry?[
    {label:'Client',detail:entry.username},
    {label:'Gateway',detail:'Authenticated & routed'},
    {label:'MCP server',detail:entry.server},
    {label:entry.server==='woocommerce'?'WooCommerce store':'Tool result',detail:entry.tool},
    {label:'Audit record',detail:`#${entry.id}`}
  ]:[];
  return <div className="overlay" onClick={onClose}><div className="drawer trace-drawer" role="dialog" aria-modal="true" aria-label="Request trace" onClick={event=>event.stopPropagation()}>
    <button className="close" onClick={onClose} aria-label="Close trace">×</button>
    <small>END-TO-END REQUEST TRACE</small><h2>Follow this request</h2>
    <p className="trace-intro">One tool call, from the client through the gateway to its audit record.</p>
    <div className="trace-id"><span>REQUEST ID</span><code>{requestId}</code><button onClick={()=>navigator.clipboard.writeText(requestId).catch(()=>{})}>Copy</button></div>
    {error&&<div className="trace-error" role="alert">{error}</div>}
    {!entry&&!error&&<p className="help">Loading the matching audit record…</p>}
    {entry&&<>
      <div className={`trace-result ${failed?'trace-failed':'trace-success'}`}><strong>{failed?'Tool call failed':'Tool call completed'}</strong><span>{entry.duration_ms} ms · {new Date(entry.at).toLocaleString()}</span></div>
      <ol className="trace-path">{path.map((node,index)=><li key={node.label} className={failed&&index>=3?'trace-node-failed':''}><span className="trace-index">{index+1}</span><div><strong>{node.label}</strong><small>{node.detail}</small></div></li>)}</ol>
      <div className="trace-facts"><div><span>Actor</span><strong>{entry.username}</strong></div><div><span>Tool</span><strong>{entry.server} / {entry.tool}</strong></div><div><span>Outcome</span><strong>{entry.outcome}</strong></div><div><span>Duration</span><strong>{entry.duration_ms} ms</strong></div></div>
      <div className="trace-actions"><button onClick={()=>{onNavigate('Servers',entry.server);onClose()}}>Inspect server →</button>{isAdmin&&<button onClick={()=>{onNavigate('Audit Log');onClose()}}>Open audit log →</button>}</div>
      <p className="help">Only metadata is stored here; credentials and request/response bodies are not included.</p>
    </>}
  </div></div>;
}
