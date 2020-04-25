Themes
------

### Create theme

Edit `app/config`:
```php
define ('WPT_THEMES', ['blue', 'green', 'red', 'orange', '{new theme}']);
```
Copy a existing theme file and customize it:
```bash
$ cp app/themes/blue.css app/themes/new_theme.css
```
