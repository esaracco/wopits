FROM debian:buster

ARG DEBIAN_FRONTEND=noninteractive
ARG WOPITS_HOST
ARG WOPITS_DB_NAME
ARG WOPITS_DB_USER
ARG WOPITS_DB_PASSWORD

RUN apt-get update && \
    apt-get install -yq apache2 php php-dev libevent-dev php-pear php-gettext php-mysql php-imagick php-zip
RUN apt-get install -yq --no-install-recommends cron sudo locales re2c rsync wget

COPY docker/app/files/etc/security/limits.d/local.conf /etc/security/limits.d/
COPY docker/app/files/etc/sysctl.d/local.conf /etc/sysctl.d/

COPY docker/app/files/etc/apache2/localhost.* /etc/apache2/
COPY docker/app/files/etc/apache2/sites-available/wopits.localhost.conf /etc/apache2/sites-available/

COPY docker/app/files/etc/php/7.3/apache2/php.ini /etc/php/7.3/apache2/
COPY docker/app/files/etc/php/7.3/cli/php.ini /etc/php/7.3/cli/

COPY docker/app/files/locale.gen /etc/

RUN locale-gen && \
    \
    mkdir -p /var/log/php && \
    useradd --gid www-data --system --create-home --no-log-init wopits && \
    \
    chown root:root /etc/apache2/localhost.* && \
    chmod 600 /etc/apache2/*.key && \
    \
    a2enmod -q ssl rewrite headers proxy_wstunnel && a2ensite -q wopits.localhost && \
    wget -q https://github.com/swoole/swoole-src/archive/refs/tags/v4.7.1.tar.gz && \
    tar xf v4.7.1.tar.gz

WORKDIR swoole-src-4.7.1
RUN phpize && ./configure && make && make install && \
    \
    pecl channel-update pecl.php.net && \
    pecl install ev && \
    pecl install event && \
    \
    mkdir -p /var/log/wopits && mkdir -p /var/www/wopits.localhost && \
    chown wopits /var/log/wopits /var/www/wopits.localhost

COPY --chown=wopits:crontab docker/app/files/crontab /var/spool/cron/crontabs/wopits
RUN chmod 600 /var/spool/cron/crontabs/wopits

COPY . /home/wopits/
WORKDIR /home/wopits
COPY docker/app/files/config-deploy.php app/deploy/config.php

RUN perl -pi -e 's/WOPITS_HOST/'${WOPITS_HOST}'/g' app/deploy/config.php && \
    perl -pi -e 's/WOPITS_DB_NAME/'${WOPITS_DB_NAME}'/g' app/deploy/config.php && \
    perl -pi -e 's/WOPITS_DB_USER/'${WOPITS_DB_USER}'/g' app/deploy/config.php && \
    perl -pi -e 's/WOPITS_DB_PASSWORD/'${WOPITS_DB_PASSWORD}'/g' app/deploy/config.php && \
    \
    mkdir -p /var/www/wopits.localhost/data/walls && \
    mkdir -p /var/www/wopits.localhost/data/users && \
    chown -R www-data:www-data /var/www/wopits.localhost/data && \
    chmod 2770 /var/www/wopits.localhost/data

# Overkill: no need to minify for this experimental docker
##RUN apt-get install -y --no-install-recommends default-jre
##RUN wget -q https://repo1.maven.org/maven2/com/google/javascript/closure-compiler/v20210505/closure-compiler-v20210505.jar -O app/deploy/bin/closure-compiler.jar && \
##    wget -q https://github.com/google/closure-stylesheets/releases/download/v1.5.0/closure-stylesheets.jar -O app/deploy/bin/closure-stylesheets.jar && \
##    wget -q https://storage.googleapis.com/google-code-archive-downloads/v2/code.google.com/htmlcompressor/htmlcompressor-1.5.3.jar -O app/deploy/bin/htmlcompressor.jar

COPY docker/app/files/docker-entrypoint.sh /usr/local/bin/
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
