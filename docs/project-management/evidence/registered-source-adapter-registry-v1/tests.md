# Tests

The full package `composer quality:gate` passed on PHP 8.4 on 2026-09-18:
launch validation, syntax for 52 PHP files, configured PHPStan, all unit and
SQLite persistence tests, Minimal CMS dependency contract, evidence and scope.

The focused unit test proves valid lookup and dataset return plus fail-closed
handling for invalid registration, duplicates, unknown sources, invalid
principals, unregistered query fields, mismatched source/query/access/count and
undeclared projection keys. It arms the adapter to throw during invalid-input
checks, proving those refusals happen before owner execution.
