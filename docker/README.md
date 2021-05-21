DOCKER
------------
The docker is still in beta, but you can already play with it.
Go to the `docker/` directory, customize the `.env` file, take a look at the `docker-compose.yml` and run `docker-compose up`. Images are not on Docker Hub. The build of the 2 images `wopits_db` (MariaDB database) and `wopits_app` (Apache & Swoole services) can take time... be patient :-)
The application needs to run on HTTPS. We use a self-signed certificate. Just ignore the browser warning.

> ***It is not intended for production! and emails will not be sent.***
> Any help is welcome!
