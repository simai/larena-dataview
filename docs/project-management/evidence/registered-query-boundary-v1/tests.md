# Tests

Package composer test passed. New meaningful test preserves a Unicode/underscore identifier; uses common sort/pagination; rejects ten invalid queries before provider rows are called; rejects malformed registration. Exact new runtime class location is asserted despite ignored shared vendor. Independent PHPStan 2.2.1 level 5 for two runtime files passed.

The full configured package PHPStan gate also passed after the initial 128M worker memory failure was resolved with a process-local PHPRC copy setting memory_limit=512M. Repository checks and global PHP configuration were unchanged. Malformed raw registrations are tested directly through the validator, respecting the typed dataset API.

A pre-fix test reproduced equal snapshot IDs for the same visible page with different total counts. Including pagination.total in the existing snapshot payload fixes count invalidation; the regression verifies identical rows, changed totals and different IDs. This changes cache IDs without changing DTO/storage shape.


## Owner-paged continuation — 2026-09-16

Full native `composer quality:gate` passed after adding the generic paged interface and its unit test: validator, 46-file lint, configured PHPStan, all unit/integration suites, Minimal CMS dependency contract, evidence and scope checks. `RegisteredPagedSourceTest` proves exactly one owner-page request, preserved opaque Unicode IDs, no second slicing, five malformed output refusals and rejection before owner callbacks. Root domain integration is recorded separately; no Chrome, clean-release or production acceptance is implied.
