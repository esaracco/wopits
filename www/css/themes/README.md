Themes
------

### Create theme

- Edit `app/config.php`:
```php
define ('WPT_THEMES', ['blue', 'green', 'red', 'orange', 'purple', 'newtheme']);
```
- Edit `app/inc/main.css.php` and add a line for the button of your new theme:
```css
.btn-theme-newtheme {
  background-color: #c63fca;
}
```
- Copy a existing theme file and customize it:
```bash
$ cp www/css/themes/blue.css.php www/css/themes/newtheme.css.php
