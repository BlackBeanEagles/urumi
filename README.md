# Urumi — Team MCP Gateway on Kubernetes

Urumi is a one-store WooCommerce demo with a team MCP gateway. An admin registers upstream MCP servers once; the dashboard chat and external MCP clients then discover their tools through one authenticated endpoint. The system runs on local Kubernetes and is packaged as one Helm chart with local and production-like values.

The checkout is for demonstration only. Use fictitious customer details and Cash on Delivery; no online payment or fulfilment is configured.

## What is included

- WooCommerce on WordPress with MariaDB and persistent volumes.
- A custom WooCommerce MCP server exposing product and order tools over Streamable HTTP.
- A Node gateway that aggregates three upstreams: WooCommerce, catalog, and clock.
- A React dashboard for integrations, chat, team keys, health, traces, and audit history.
- Admin and member roles, personal revocable API keys, and a local Ollama-backed chat agent.
- A Helm chart for the same six application workloads in local and k3s-style environments.

~~~text
Customer browser ── store.localhost ── Ingress ── WordPress + WooCommerce ── MariaDB
Admin/member browser ── gateway.localhost ── Ingress ── Gateway + React UI
External MCP client ── gateway.localhost/mcp ────────── Gateway
                                                    ├── SQLite PVC (users, servers, audit)
                                                    ├── LLM provider (dashboard chat)
                                                    └── internal MCP Services
                                                        ├── WooCommerce MCP ── WordPress REST
                                                        ├── catalog MCP
                                                        └── clock MCP
~~~

Only the gateway and storefront have Ingress routes. The MCP upstreams are ClusterIP Services; an ingress NetworkPolicy permits the gateway pod to reach them. See [DESIGN.md](DESIGN.md) for request flow and tradeoffs, and [THREAT_MODEL.md](THREAT_MODEL.md) for trust boundaries and residual risks.

## Repository map

- <code>src/gateway.js</code>, <code>src/db.js</code>, <code>src/mcp.js</code>: dashboard API, MCP endpoint, routing, users, audit, and persistence.
- <code>src/upstream.js</code>: the custom WooCommerce, catalog, and clock MCP servers.
- <code>web/</code>: React dashboard and its styles; <code>helm/urumi/files/urumi-storefront-*</code>: WordPress storefront must-use plugins.
- <code>helm/urumi/</code>: chart, templates, local values, production-like values, and chart-packaged source/assets.
- <code>assets/catalog/</code>, <code>scripts/seed-demo-products.php</code>: optional photographed demo catalog.
- <code>test/</code>: chat approval and read-only tool-policy tests.

The chart mounts copies of several source files from <code>helm/urumi/files/</code> into the pods. When changing <code>src/gateway.js</code>, <code>src/db.js</code>, <code>src/chat-policy.js</code>, or <code>src/upstream.js</code>, update the corresponding chart copies before a Helm upgrade. Local values also enable a ConfigMap override from <code>helm/urumi/files/web/</code>; production values use the UI bundled in the gateway image.

## Local setup (Docker Desktop Kubernetes)

The recorded run used Docker Desktop Kubernetes on Windows, Traefik, Node 24, Helm 3, and local Ollama with <code>llama3.1:8b</code>. A Kind or k3d cluster can use the same chart after loading the locally built images and providing an Ingress controller. A CNI must implement NetworkPolicy for the gateway-only rule to be enforced; that enforcement was not independently verified on Docker Desktop.

1. Install dependencies, run tests, and build both images from the repository root:

   ~~~powershell
   npm ci
   npm test
   npm run build:web
   docker build -t urumi-gateway:local -f Dockerfile .
   docker build -t urumi-upstream:local -f Dockerfile.upstream .
   ~~~

   If using Kind or k3d, load both images into that cluster before installing the chart. The local chart sets <code>imagePullPolicy: IfNotPresent</code>.

2. Make sure an Ingress controller is available. For the tested Traefik route:

   ~~~powershell
   helm upgrade --install traefik traefik/traefik --repo https://traefik.github.io/charts -n traefik --create-namespace --wait
   ~~~

   Local hosts are <code>gateway.localhost</code> and <code>store.localhost</code>. They resolved to loopback on the tested machine, with Traefik reachable on port 80. If your client does not resolve them, configure local DNS/hosts accordingly; the Ingress depends on the hostname, not just the loopback IP.

3. Create an ignored <code>my-secrets.yaml</code> in the repository root (or a private file elsewhere). Replace the three password placeholders. To enable chat with Ollama on the Docker host, run <code>ollama pull llama3.1:8b</code> and keep the Ollama service running. The local model ignores the non-empty placeholder API key, but the gateway requires a non-empty value to enable chat.

   ~~~yaml
   secrets:
     mariadbPassword: "<unique-database-password>"
     mariadbRootPassword: "<unique-root-password>"
     gatewayAdminPassword: "<unique-admin-password>"
     llmApiKey: "local-ollama"
   llm:
     baseUrl: "http://host.docker.internal:11434/v1"
     model: "llama3.1:8b"
   ~~~

   Never commit actual values. For another tool-capable model, replace the provider URL, model, and API key.

4. Create the namespace and storefront asset ConfigMap, then install the chart. Do not use <code>--wait</code> on the first install: the WooCommerce MCP pod cannot start until WordPress is installed and its REST credentials exist.

   ~~~powershell
   kubectl create namespace urumi
   kubectl create configmap urumi-storefront-assets -n urumi --from-file=assets/storefront
   helm upgrade --install urumi helm/urumi -n urumi -f helm/urumi/values-local.yaml -f my-secrets.yaml
   kubectl get pods,ingress,pvc -n urumi
   ~~~

   The first two commands are for a fresh namespace. On later upgrades, retain the existing persistent volumes and asset ConfigMap. The WordPress init container copies the storefront assets into its persistent content volume.

5. Open <code>http://store.localhost/</code>, finish WordPress installation, and sign in to <code>/wp-admin/</code>. Install and activate WooCommerce, complete its store setup, and enable **Cash on Delivery** under WooCommerce payment settings. Under **WooCommerce → Settings → Advanced → REST API**, create a read/write consumer key for the MCP connector.

6. Put that consumer key and secret in a private, ignored file named <code>woo-secrets.local.yaml</code>, then apply it. This Secret is intentionally separate because the key cannot be issued until WooCommerce exists.

   ~~~yaml
   apiVersion: v1
   kind: Secret
   metadata:
     name: urumi-woo-credentials
     namespace: urumi
   type: Opaque
   stringData:
     consumer-key: "<generated-consumer-key>"
     consumer-secret: "<generated-consumer-secret>"
   ~~~

   ~~~powershell
   kubectl apply -f woo-secrets.local.yaml
   kubectl rollout restart deployment/woocommerce-mcp -n urumi
   kubectl rollout status deployment/woocommerce-mcp -n urumi
   kubectl get pods -n urumi
   ~~~

   The WooCommerce connector uses the REST API: OAuth 1.0a HMAC-SHA256 against the local HTTP WordPress endpoint, and Basic authentication only when the WordPress endpoint is HTTPS. The consumer secret stays in the MCP pod; it is not returned to clients.

7. Verify both public routes:

   ~~~powershell
   curl.exe --noproxy "*" http://gateway.localhost/healthz
   curl.exe --noproxy "*" -I http://store.localhost/
   ~~~

   Sign in at <code>http://gateway.localhost/</code> as <code>admin</code> with the private <code>gatewayAdminPassword</code>. **Overview** should show WooCommerce, catalog, and clock. If chat is unavailable, check the Ollama service and the model status in **AI Chat**.

### Optional demo products and photographs

The storefront works with ordinary WooCommerce products. For the photographed demo collection, <code>scripts/seed-demo-products.php</code> creates products idempotently by SKU and imports the matching <code>assets/catalog/</code> images into WordPress Media Library. After WooCommerce is active, run from the repository root:

~~~powershell
$wpPod = kubectl get pod -n urumi -l app=wordpress -o jsonpath='{.items[0].metadata.name}'
kubectl cp scripts/seed-demo-products.php "urumi/${wpPod}:/tmp/seed-demo-products.php"
kubectl cp assets/catalog "urumi/${wpPod}:/tmp/urumi-product-images"
kubectl exec -n urumi $wpPod -- php /tmp/seed-demo-products.php
~~~

This is a local demo step, not an automatic production migration. The published catalog and images persist on the WordPress volume.

## Use the system

### Place an order

Open <code>http://store.localhost/shop/</code>, choose a product, select **Add to bag**, open **Bag**, and proceed to checkout. Enter fictitious details, select **Cash on Delivery**, and place the test order once. Confirm its order number on the receipt and in **WooCommerce → Orders** at <code>http://store.localhost/wp-admin/</code>. The recorded demo used an AI-created Sage Canvas Weekender and verified matching order #71 for INR 799.

### Use the dashboard chat

At <code>http://gateway.localhost/</code>, sign in and open **AI Chat**. Ask about products or an order. The right-hand **Tool execution** panel shows actual calls and results. A request to create a product or perform another non-allowlisted action pauses for **Confirm & run**; no chat write occurs before approval. The chat uses the signed-in user's identity and the same gateway discovery/routing code as the external MCP endpoint.

### Connect an external MCP client

In **Team & API Keys**, add a member if needed. The member signs in, selects **Create key**, and copies the personal key when it is shown once. Configure a Streamable HTTP client with:

- URL: <code>http://gateway.localhost/mcp</code>
- Header: <code>Authorization: Bearer &lt;member-key&gt;</code>

For Continue in VS Code, place this entry in the user's private Continue configuration and select **Agent** mode with a tool-capable model:

~~~yaml
mcpServers:
  - name: Urumi member
    type: streamable-http
    url: http://gateway.localhost/mcp
    requestOptions:
      headers:
        Authorization: "Bearer <member-key>"
~~~

Keep client configuration and keys out of Git and out of screen recordings. A connected client should discover prefixed tools such as <code>woocommerce__list_orders</code>, <code>catalog__store_info</code>, and <code>clock__current_time</code>. A member never configures upstream URLs or receives the WooCommerce REST key. Revoking that member's key rejects its next gateway request.

### Inspect failures and audits

Admins can disable an upstream from **Servers**; discovery then omits that server's tools while other servers remain usable. **Audit Log** records executed tool calls with actor, server/tool, timestamp, duration, outcome, and request ID. The trace drawer connects a call to its audit record. Upstream connection failures are reported as unreachable; a failed upstream does not prevent healthy upstreams from being discovered.

## Production-like k3s configuration

The same <code>helm/urumi</code> chart is used with <code>helm/urumi/values-prod.yaml</code> plus a private values file. This is a documented deployment path, not a claim that a VPS deployment has been run.

1. Build and publish the gateway and upstream images to a registry accessible by k3s. Replace the example registry paths and tags in the production values.
2. Point real DNS names at the VPS/Ingress endpoint, choose the correct Ingress class, and set <code>host.dashboard</code>, <code>host.store</code>, and <code>storePublicUrl</code>. The chart currently defines host-based HTTP Ingress rules, not a TLS block; terminate HTTPS with a configured Ingress/reverse proxy before exposing credentials on the Internet.
3. Choose a k3s storage class (the example uses <code>local-path</code>, appropriate only for a single-node-style setup), prepare private passwords and a reachable LLM provider, and create the storefront asset ConfigMap. Bootstrap WordPress and the separate WooCommerce REST Secret as in the local steps.
4. Install with:

   ~~~sh
   helm upgrade --install urumi helm/urumi -n urumi --create-namespace -f helm/urumi/values-prod.yaml -f my-secrets.yaml
   ~~~

   Ensure the chart-packaged files in <code>helm/urumi/files/</code> match the source being shipped; the Deployments mount those copies even when the image includes source code.

Before an upgrade, back up MariaDB, WordPress uploads/content, and the gateway SQLite volume. Use <code>helm history urumi -n urumi</code> to inspect revisions and <code>helm rollback urumi &lt;revision&gt; -n urumi</code> to roll back manifests. A database or WordPress schema migration may also require restoring a backup; Helm rollback alone cannot undo data changes.

## Stand-out items and limits

- **Customer-facing store and photographed catalog:** the storefront lives in <code>helm/urumi/files/urumi-storefront-retail.*</code>; image assets and the optional seeder are in <code>assets/</code> and <code>scripts/seed-demo-products.php</code>.
- **AI write-action review:** <code>src/chat-policy.js</code>, <code>src/gateway.js</code>, and <code>test/</code> implement and test a single-use approval step before dashboard chat performs non-allowlisted tool calls.
- **Traceable multi-server calls:** <code>src/gateway.js</code>, <code>src/db.js</code>, and <code>web/TracePanel.jsx</code> connect executed calls to duration, outcome, actor, and request ID.
- **Team access and isolation from upstreams:** personal revocable keys, prefixed aggregation, and gateway-only MCP access are implemented in <code>src/</code>; the upstream ingress rule is in <code>helm/urumi/templates/networkpolicy.yaml</code>.

The documented scope is one WooCommerce store and three MCP upstreams. Multi-store provisioning, OAuth, hosted upstream deployment, and horizontal gateway scaling were not attempted. Per-tool member permissions, encrypted upstream credentials, production-grade sessions, and a tested VPS deployment remain future work. Chat approval does not restrict an external MCP client from calling an exposed write tool directly.
