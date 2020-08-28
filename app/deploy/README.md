### Deployment

- Move to `app/deploy/`.
- Create a new file `config.php` in this directory by duplicating `config.example.php`, and customize it.
- In order to minify JS, CSS or HTML, you will need to customize the `minifiers` section. Read `config.example.php` for more information.
- Deploy the application by executing `./deploy -e[yourenv]`. `yourenv` should have been defined in the new `config.php` you just created previously. If the target is located on remote, the SSH user must have full rights on the remote DocumentRoot.
- The very first time the deployment script has been executed, you will have to log as root and execute the following commands before creating a service for the wopits WebSocket daemon:
```bash
# chown -R [wopitsUser]:[wopitsUserGroup] /var/www/wopits.domain.com/
# cd /var/www/wopits.domain.com/
# mkdir -p data/{walls,users}
# chown -R [ApacheUser]:[wopitsUserGroup] data
# chmod 2770 data
```
Right after each deployment you must execute as soon as possible the following post-deployment script **as root**:
```bash
# /var/www/wopits.domain.com/app/deploy/bin/post-deploy.php
```
