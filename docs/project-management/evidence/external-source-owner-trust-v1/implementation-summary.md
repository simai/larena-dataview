# Implementation summary

- `DataviewSourceDescriptor` now validates the owner as a Composer package
  identity instead of requiring the `larena/` vendor prefix, and exposes
  `isFirstParty()` so trust decisions stay outside the identity.
- `RegisteredDataviewSourceRegistry` accepts an explicit host-declared list of
  trusted external owner packages. A tagged adapter whose owner is not declared
  fails closed with `dataview_source_owner_not_trusted`.
- An invalid trust entry fails closed with `dataview_source_owner_trust_invalid`.
- The read-only table adapter guardrail now proves owner mismatch by descriptor
  identity comparison rather than by vendor prefix.
- Access scoping, canonical-record refusal, query validation and snapshot
  validation are unchanged.
- Added `RegisteredSourceAccessDenied` so an owner can refuse a principal through
  a shared contract instead of a vendor-specific error string.
