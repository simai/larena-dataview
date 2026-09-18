# Changelog

## Unreleased

### Changed

- Added a fail-closed registered source-adapter contract and registry so domain packages can contribute authorized datasets without Dataview owning their records, permissions or presentation.

- Completed Minimal CMS B10 with one access-filtered dataset snapshot projected as table, cards, calendar, kanban, Gantt and tree.
- Added shared filtering, deterministic sorting, pagination and interaction policy across all six descriptors.
- Added type-specific field-role validation and explicit proof that Dataview neither owns nor persists source records.
- Added canonical persisted Dataview descriptors with integrity hashes, immutable revisions, optimistic concurrency and rollback for constructor-managed views.

- Added Core and Property as Dataview's exact mandatory Larena Composer dependencies and pinned lock resolution to the supported PHP 8.3 platform.

### Documentation

- Record the accepted Minimal CMS v1 read-only Dataview boundary for six descriptors over one dataset.

### Non-claims

- No runtime behavior, public contract or package version changes are introduced by the B0 preparation.
