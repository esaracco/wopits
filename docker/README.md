DOCKER
------------
Go to the `docker/` directory, customize the `.env` file, take a look at `docker-compose.yml` and run `docker-compose build` & `docker-compose up`. Images are not on Docker Hub. The build of the 2 images `wopits_db` (MariaDB database) and `wopits_app` (Apache & Swoole services) can take a while... be patient :-)
The application needs to run on HTTPS. We use a self-signed certificate. Just ignore the browser warning.

> ***Emails will not be sent, and this docker is not intended for production use!***
