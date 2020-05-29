#### DKIM

> The use of DKIM for sending emails is not mandatory, but highly recommended if your SMTP server does not already manage it.

The wopits DKIM private key must be placed in `app/dkim/dkim.private`.
You can generate a pair of keys using:
```bash
$ openssl genrsa -out dkim.private 1024
$ openssl rsa -in dkim.private -out dkim.public -pubout -outform PEM
```
If you are using wopits as is, without depoyment, you must edit `site-config.php`:
```php
define ('WPT_USE_DKIM', true);
define ('WPT_DKIM_DOMAIN', 'domain.com');
define ('WPT_DKIM_SELECTOR', 'mail');
```
If you plan to deploy wopits, edit `app/deploy/config.php` and customize the following lines before adding them into your deployment environment settings:
```php
'dkim' => [
  'domain' => 'domain.com',
  'selector' => 'mail'
]
```
**The following is needed only after the very first deployment, or later if you update your key**:
Copy your private key in `app/dkim/dkim.private` on the target. Then:
```bash
# cd [DocumentRoot]
# chown [Apache user] app/dkim/dkim.private
# chmod 400 app/dkim/dkim.private
```
