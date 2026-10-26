# Threat model

Personal API keys are stored as SHA-256 hashes. Revocation removes access on the next request. Login passwords are salted and hashed using scrypt. Users do not receive upstream credentials. The gateway uses its shared WooCommerce credential for each authorized user, so a member can currently perform every exposed WooCommerce action; per-tool authorization is a recommended next step.

Client bearer tokens are only checked by the gateway. They are never forwarded to upstreams, preventing upstream operators from stealing or replaying client credentials. Upstream credentials are sent only from the gateway to the configured internal endpoint. The gateway's API restricts upstream URLs to Kubernetes service DNS names. A malicious service inside the cluster is still a risk, and DNS rebinding or compromised cluster DNS needs separate hardening.

Tool descriptions and outputs are untrusted. The chat system message instructs the model to ignore instructions inside them, but model prompting is not a complete defense. A compromised upstream can produce misleading data or attempt prompt injection. Team members can call destructive WooCommerce tools such as order-status updates; they cannot manage servers or users, but they can affect shared store data. Audit records attribution but do not prevent misuse.

Kubernetes Secrets protect credentials from being placed in source code. They are not encrypted by this application at rest. Limit cluster RBAC access to Secrets and consider encryption at rest for k3s. A NetworkPolicy permits gateway pods to call MCP pods; enforcement depends on the cluster CNI.
