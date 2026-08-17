# Concept alignment: Larena Dataview

## Accepted role

Dataview owns read-only presentation descriptors for a single access-filtered dataset: table, cards, calendar, kanban, Gantt and tree. It does not mutate or copy source records.

Accepted Target State: `larena.target.minimal_cms_v1` at semantic digest `sha256:2793f61ba9563839831d57e87ac5cd6399c37a3183f1a68981b6fc7f941a1ad2`.

## Dependency and ownership boundary

- Mandatory Larena dependencies: Core and Property.
- Storage supplies records through contracts at integration time; it is not a mandatory Dataview dependency.
- UI renders descriptors but remains a consumer.

## Continuation strategy

Existing view definitions remain where compatible. B2 establishes the lower dependency graph; B10 completes six registered descriptors plus shared filtering, sorting, pagination and interaction policy.

## B10 alignment status

Core and Property remain the exact mandatory Larena Composer dependencies. B10 loads one access-filtered dataset snapshot, applies shared filtering, sorting and pagination once, and projects that same immutable result through six registered descriptor types. Type-specific roles are validated for cards, calendar, kanban, Gantt and tree; Dataview creates no persistence tables.

## Install and rollback baseline

Install through the Root Composer lock and package discovery. Dataview creates no record-copy tables. B0 is documentation-only; rollback restores the previous verified Root lock and descriptor definitions without touching source data.

## Verification

Run package tests, `composer validate`, dependency reporting and descriptor tests proving common source identity and no data-copy tables.
