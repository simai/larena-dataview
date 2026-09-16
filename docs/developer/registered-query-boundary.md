# Registered dataset query boundary (candidate)

Use `DataviewDatasetRuntime::loadRegistered(provider, query, fields)` for new registered list sources. This validates before the existing dataset runtime accesses provider rows. Keep domain permission filtering in the provider owner; `accessScoped` metadata is not authorization.

Field registrations are trusted PHP/manifest data selected by an allowlisted source registry, never copied from request or Recipe JSON. Every field declares scalar `type`, permitted `operators` and `sortable`. Supported types are string, integer, finite number, boolean and opaque string/integer identifier. Null filters are unsupported in this candidate. Add any expansion with explicit typed tests.

Requests use the existing DataviewQuery contract. Extra filter/sort fields, unknown fields/operators, wrong value types, oversized lists/text, repeated sorts and excessive filters are refused before row reads. IDs are preserved without splitting underscores, decoding DOM cells or inferring ownership.

The source supplies access-filtered normalized scalar query rows. Complex display-cell objects remain a separate owner presentation projection; do not filter badge/link objects as raw values. Domain adapters still need to map raw query fields to existing UI fields. No SQL, endpoint, class name, permission grant or callback comes from the composition document.

Legacy `load` remains compatible and does not automatically inherit this guard. Existing HTTP consumers must adopt the guarded path explicitly and pass direct-request ACL/query tests. The legacy in-memory dataset runtime is retained. For owner-paged sources use `loadRegisteredPage` as described below.

Pending: release integration of both domain adapters, public interaction ports, SSR/hydration, action idempotency, HTTP and Chrome acceptance, Specs/Docs mappings and exact Framework 1.0.1 supply. This is not interactive list readiness.

Dataset snapshot identity includes total count as well as the effective page, size, query and visible rows. A count change invalidates identity even if the visible slice is unchanged. Old cached IDs are naturally superseded; no persisted descriptor migration is required.

## Owner-paged source contract

`DataviewPagedSourceProvider` exposes `descriptor()` and `page(query)` without requiring whole-dataset `rows()`. `loadRegisteredPage(provider, query, fields)` guards the registered query before requesting exactly one owner page. It verifies source/query identity, access-filtered status, pagination, row count and declared projection keys. It returns the owner snapshot without a second slice or sort. The provider owns domain permissions and the actual query implementation; this interface alone does not prove SQL scalability.

Adapters may expose only their native supported operators. Unsupported operators, duplicate predicates that cannot be faithfully represented, and business-field/metadata collisions must fail explicitly. Never weaken a query or substitute global search for a field predicate. Optional application bridges keep Storage/Auth dependencies out of Dataview's mandatory Minimal CMS composition.

The initial Root Storage bridge supports equality filters and native owner sorting/paging; it preserves scope and structure identity. Storage currently scans at most 500 records internally before paging. That limitation remains an owner optimization task, not a scalable query claim. Global search and public action ports still require their own contract acceptance.
