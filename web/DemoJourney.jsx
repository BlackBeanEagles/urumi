import React from 'react';

export default function DemoJourney({servers,audit,startedAt,storeUrl,storeVerified,traceSeen,onStoreVerified,onNavigate,onClose}) {
  const recent=audit.filter(item=>Date.parse(item.at)>=startedAt);
  const productCreated=recent.some(item=>item.server==='woocommerce'&&item.tool==='create_product'&&item.outcome==='success');
  const ordersRead=recent.some(item=>item.server==='woocommerce'&&item.tool==='list_orders'&&item.outcome==='success');
  const checks=[
    {title:'Gateway is ready',detail:'The gateway sees every enabled upstream.',done:servers.length>0&&servers.filter(s=>s.enabled).every(s=>s.status==='Connected'||s.status==='Slow'),action:'Inspect topology',go:()=>onNavigate('Overview')},
    {title:'Create a product with AI',detail:'Approve the live write, then look for a successful WooCommerce call.',done:productCreated,action:'Open AI Chat',go:()=>onNavigate('AI Chat','Create a test product called Blue Hoodie for 999')},
    {title:'Verify the storefront and checkout',detail:'Open the store, place a test order, then mark this manual check complete.',done:storeVerified,action:'Open Store ↗',go:()=>storeUrl&&window.open(storeUrl,'_blank','noopener,noreferrer')},
    {title:'Ask about the order',detail:'The chat should call list_orders after checkout and show the new order.',done:ordersRead,action:'Ask AI',go:()=>onNavigate('AI Chat','What orders came in today?')},
    {title:'Inspect the exact audit trace',detail:'Open a tool call trace and confirm actor, server, outcome and duration.',done:traceSeen,action:'Open Audit Log',go:()=>onNavigate('Audit Log')}
  ];
  return <div className="overlay" onClick={onClose}><div className="drawer demo-drawer" role="dialog" aria-modal="true" aria-label="Live demo journey" onClick={event=>event.stopPropagation()}>
    <button className="close" onClick={onClose} aria-label="Close journey">×</button><small>LIVE DEMO / REAL SYSTEM</small><h2>Prove the whole path</h2>
    <p>These steps use live gateway health and audit records since this walkthrough began. Store checkout is confirmed by you.</p>
    <div className="journey-progress"><strong>{checks.filter(check=>check.done).length} / {checks.length}</strong><span>steps verified</span></div>
    <ol className="journey-list">{checks.map((check,index)=><li key={check.title} className={check.done?'journey-done':''}><span className="journey-number">{check.done?'✓':index+1}</span><div><strong>{check.title}</strong><p>{check.detail}</p><button onClick={check.go}>{check.action}</button>{index===2&&<button className="manual-check" onClick={onStoreVerified}>{storeVerified?'Marked complete ✓':'I placed and verified the order'}</button>}</div></li>)}</ol>
    <p className="help">A completed step is evidence in the current session, not a simulated success. Open the trace to verify the actual order result.</p>
  </div></div>;
}
