### Deployment

- Move to `app/deploy/`.
- Create a new file `config.php` in this directory by duplicating `config.example.php`, and customize it.
- In order to minify JS, CSS or HTML, you will need to customize the `minifiers` section. Read `config.example.php` for more information.
- Deploy the application by executing `./deploy -e[yourenv]`. `yourenv` should have been defined in the new `config.php` you just created previously. If the target is located on remote, the SSH user must have full rights on the remote DocumentRoot.
- The very first time the deployment script has been executed, you will have to log as root and execute the following commands before creating a service for the wopits WebSocket daemon:
```bash
# cd [DocumentRoot]
# mkdir -p data/{walls,users}
# chown -R [Apache user]:[Apache user] data
# chmod 2770 data
```
- At each deployment you must broadcast new release announce to all connected clients, reload apache and restart the WebSocket daemon.
```bash
$ /var/www/wopits.domain.com/app/websocket/client.php -n
# systemctl reload apache2
# systemctl restart wopits
```
The post-deployment script will do this for you (execute it as root):
```bash
# /var/www/wopits.domain.com/app/deploy/bin/post-deploy.php
```
