# Tests

- `composer run quality:gate`
- `php -d zend.assertions=1 -d assert.exception=1 tests/Unit/DataviewSixViewRuntimeTest.php`
- `composer validate --strict --no-check-publish`

The focused test verifies one filtered/sorted/paginated source, all six views, shared snapshot identity, read-only policy and absence of a Dataview database directory.
