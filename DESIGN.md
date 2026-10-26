# System design and tradeoffs

## Architecture

~~~mermaid
flowchart LR
  Browser[Storefront browser] -->|Ingress| WP[WordPress + WooCommerce]
  WP --> MariaDB[(MariaDB PVC)]
  Team[Dashboard / AI Chat] -->|Ingress| Gateway[Node gateway]
  Client[External MCP client] -->|Bearer key, /mcp| Gateway
  Gateway --> SQLite[(SQLite PVC)]
  Gateway -->|tool calls| Woo[WooCommerce MCP]
  Gateway -->|tool calls| Catalog[Catalog MCP]
  Gateway -->|tool calls| Clock[Clock MCP]
  Woo -->|REST API, shared consumer key| WP
  Gateway -->|chat completions| LLM[Ollama or compatible provider]
~~~

The Helm chart runs six Deployments: gateway, WordPress, MariaDB, WooCommerce MCP, catalog MCP, and clock MCP. It exposes only the gateway and storefront through Ingress. Each MCP upstream is a ClusterIP Service, with an ingress NetworkPolicy admitting gateway pods; actual enforcement depends on the Kubernetes CNI. MariaDB, WordPress content, and gateway SQLite each have persistent storage.

The gateway serves the React admin UI, its authenticated dashboard API, the chat-agent loop, and a Streamable HTTP MCP endpoint at <code>/mcp</code>. The external client needs only that URL and its personal bearer key. WooCommerce is a separate MCP server that translates tool calls to WooCommerce REST requests. The catalog and clock servers provide independent tools for multi-server discovery and use.

## Aggregation, routing, and failures

An admin registers internal upstream URLs and can enable or disable them. Discovery queries enabled servers in parallel with a 2.5-second timeout per server, normalizes their tool names, and publishes them as <code>&lt;server&gt;__&lt;tool&gt;</code>. The gateway maps a prefixed call back to the owning server and enforces a 10-second upstream call timeout. An unavailable server yields an error for its tools while healthy servers remain usable. Disabling a server removes its tools from subsequent discovery.

Every executed call gets an audit record with actor, server, tool, timestamp, duration, outcome, and request ID; bodies and credentials are not intentionally stored in the audit table. The dashboard trace links to these records. The AI chat uses the same discovery and call path as an external MCP client. The dashboard chat requires user confirmation for non-allowlisted tool actions, with a short-lived single-use approval bound to that user. That approval is a chat safeguard, not a gateway-wide per-tool authorization rule: an authenticated external member can directly call any tool the gateway exposes.

## State and deployment tradeoffs

Personal API keys are stored as SHA-256 hashes and can be revoked; passwords use salted scrypt hashes. The gateway's configured upstream credential, if any, is stored in plaintext in SQLite. The WooCommerce REST consumer key is instead supplied to the WooCommerce MCP pod through a Kubernetes Secret. Client bearer keys are not passed to upstreams.

SQLite makes the demo simple to run and inspect but supports only one gateway replica here. WordPress and WooCommerce require a two-stage bootstrap: WordPress is installed first, then its REST key is issued and the WooCommerce MCP Secret is created. The photographed product seeder is optional and is not a migration system. Helm packages copies of backend source in <code>helm/urumi/files/</code>; source and packaged copies must stay synchronized.

Local values use <code>gateway.localhost</code>/<code>store.localhost</code>, locally built images, Docker-host Ollama, and the local Ingress setup. Production-like values switch to registry images, real hostnames, an Ingress class, storage class, and private secrets. The included production values are an example, not a tested VPS deployment. TLS termination, DNS, ingress reachability, backups, secret management, and a CNI that enforces NetworkPolicy are deployment responsibilities. The chart does not currently declare TLS. Helm rollback restores manifests, not persistent database or WordPress data; take backups before upgrades that may change schemas or content.

For an Internet-facing service, replace single-replica SQLite with a shared database and migration process; encrypt stored upstream credentials; strengthen dashboard sessions and member/tool policy; and add end-to-end deployment and recovery tests.
