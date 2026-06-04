# Tests

Commands executed:

```bash
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer validate --strict
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer dump-autoload
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run validate:larena
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run lint
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run analyse
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run test
```

Result: passed.

Covered assertions:

- source descriptors require owner package, access scope and no canonical record ownership;
- field descriptors require property type references and localization labels;
- advanced views fail closed when capability is locked;
- side-effect actions require access, audit and source validation;
- projections cannot claim source data ownership;
- public saved views cannot contain private filters.
