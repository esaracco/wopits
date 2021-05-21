FROM mariadb:10.3.27

ARG WOPITS_DB_USER
ARG WOPITS_DB_PASSWORD
ARG WOPITS_DB_NAME

COPY init.sql /docker-entrypoint-initdb.d/

RUN /usr/bin/perl -pi -e 's/WOPITS_DB_USER/'${WOPITS_DB_USER}'/g' /docker-entrypoint-initdb.d/init.sql && \
    /usr/bin/perl -pi -e 's/WOPITS_DB_PASSWORD/'${WOPITS_DB_PASSWORD}'/g' /docker-entrypoint-initdb.d/init.sql && \
    /usr/bin/perl -pi -e 's/WOPITS_DB_NAME/'${WOPITS_DB_NAME}'/g' /docker-entrypoint-initdb.d/init.sql
