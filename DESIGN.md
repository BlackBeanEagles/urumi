# Design and tradeoffs

The gateway is the only client for upstream MCP servers. Clients authenticate with personal API keys; server credentials stay in the gateway database and are attached only to upstream requests. Tool names use `<server>__<tool>` to handle collisions. Discovery runs in parallel with a 2.5 second timeout per server. Calls have a 10 second timeout. Failures are isolated to the affected server.

WooCommerce is accessed through its REST API using a read/write consumer key. WordPress and MariaDB data use persistent claims. The deployment uses the same chart locally and on k3s, with values changing image locations, hosts, and storage class.

The gateway stores state in SQLite. One replica is supported. A future production deployment should use a shared database, encrypt upstream credentials at rest, add migrations and backups, and use stronger dashboard sessions. The current chat uses any OpenAI-compatible chat-completions provider with tool calling.
