# Tests

`composer quality:gate` passed on PHP 8.4: launch validation, lint, PHPStan, all
unit and SQLite persistence tests, Minimal CMS dependency contract, evidence and
scope checks.

`tests/Unit/RegisteredDataviewSourceRegistryTest.php` additionally proves:

- an undeclared external owner is rejected before any adapter call;
- a malformed trust entry is rejected;
- declaring an unrelated package does not grant trust;
- a declared external owner registers, queries and returns a validated snapshot
  whose descriptor reports `isFirstParty() === false`;
- a malformed owner identity is still rejected as an invalid registration;
- `RegisteredSourceAccessDenied` carries the refused source key and a fixed
  message, so a host can answer with a denial without owner policy detail.
