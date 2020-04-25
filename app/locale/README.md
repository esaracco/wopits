Translations
---------------

### Usage
- `gettextctl create` to create PO.
- `gettextctl merge` to merge new items within PO.
- `gettextctl compile` to compile PO and install them.

### Create translation
- Edit `app/config.php`:
```php
  define ('WPT_LOCALES', [
    'en' => 'Europe/London',
    'fr' => 'Europe/Paris',
    '{new locale}' => '{default timezone}'
  ]);
```
- Edit `app/locale/gettextctl`:
```bash
LOCALES=(en fr {new locale})
```
- Add the image of the new translation: `img/locale/{new locale}-24.png`.
- Create the gettext PO file:
```bash
$ app/locale/gettextctl create
```
- Edit `{new locale}/LC_MESSAGES/wopits.po`.
