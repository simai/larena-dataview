# Focused review

The B10 delta stays inside Dataview ownership. Source identity remains `larena/storage`, but integration occurs through `DataviewSourceProvider`, not a Composer dependency. All six projections carry the same snapshot digest and rows, and the projection set rejects missing or duplicate types.
