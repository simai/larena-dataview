# Registered Dataview source adapters

`RegisteredDataviewSourceRegistry` is the package-owned extension boundary for
product/domain lists. A host selects only an allowlisted `sourceKey`; request
JSON never supplies a class, callback, query fields, permissions or presentation.

Each `RegisteredDataviewSourceAdapter` publishes a valid
`DataviewSourceDescriptor`, trusted query fields and trusted search fields. Its
owner executes the query for an explicit principal and returns an
access-filtered `DataviewDatasetSnapshot`. Auth, Storage or another domain
package continues to own authorization and canonical records.

The registry freezes and validates all registrations when it is constructed.
It rejects invalid or duplicate source keys, malformed field/search contracts,
unknown sources, invalid principals and unregistered queries before invoking an
adapter. It then checks source/query identity, access filtering, pagination,
row count and projection keys before returning the snapshot.

The registry deliberately does not own columns, labels, routes, actions or
other presentation. Those remain trusted host/UI configuration. It also does
not discover adapters by class name: the host container builds the explicit
adapter list. This keeps package dependencies optional and makes a missing
owner adapter fail closed.

Existing `DataviewSourceProvider` and `DataviewPagedSourceProvider` remain valid
lower-level contracts. An adapter may delegate to the existing
`DataviewDatasetRuntime` guarded paths rather than reimplement filtering or
paging.
