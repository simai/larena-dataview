# Implementation

RegisteredQueryValidator checks server-owned field/operator/type/sort contracts before row access. DataviewDatasetRuntime.loadRegistered delegates to the existing load implementation after validation, preserving common filtering/sort/pagination. Existing load calls retain compatibility and are not claimed guarded by this new path. No new list renderer or Framework event is introduced.
