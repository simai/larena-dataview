# Registered dataset query boundary (candidate)

Use `DataviewDatasetRuntime::loadRegistered(provider, query, fields)` for new registered list sources. This validates before the existing dataset runtime accesses provider rows. Keep domain permission filtering in the provider owner; `accessScoped` metadata is not authorization.

Field registrations are trusted PHP/manifest data selected by an allowlisted source registry, never copied from request or Recipe JSON. Every field declares scalar `type`, permitted `operators` and `sortable`. Supported types are string, integer, finite number, boolean and opaque string/integer identifier. Null filters are unsupported in this candidate. Add any expansion with explicit typed tests.

Requests use the existing DataviewQuery contract. Extra filter/sort fields, unknown fields/operators, wrong value types, oversized lists/text, repeated sorts and excessive filters are refused before row reads. IDs are preserved without splitting underscores, decoding DOM cells or inferring ownership.

The source supplies access-filtered normalized scalar query rows. Complex display-cell objects remain a separate owner presentation projection; do not filter badge/link objects as raw values. Domain adapters still need to map raw query fields to existing UI fields. No SQL, endpoint, class name, permission grant or callback comes from the composition document.

Legacy `load` remains compatible and does not automatically inherit this guard. Existing HTTP consumers must adopt the guarded path explicitly and pass direct-request ACL/query tests. The current in-memory dataset runtime is retained; server-side pagination support is separate work, not claimed by this guard.

Pending: two real domain adapters, public interaction ports, SSR/hydration, action idempotency, HTTP and Chrome acceptance, Specs/Docs mappings and exact Framework 1.0.1 supply. This is not interactive list readiness.

Dataset snapshot identity includes total count as well as the effective page, size, query and visible rows. A count change invalidates identity even if the visible slice is unchanged. Old cached IDs are naturally superseded; no persisted descriptor migration is required.
