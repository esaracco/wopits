#!/bin/bash

echo "########## PLEASE WAIT for wopits deployment..."

# Block output for the following commands
{

  # (as user "wopits") Deploy wopits -> "-y" assume yes
  sudo -Eu wopits /home/wopits/app/deploy/deploy -y -edocker

  # Run Swoole Task server
  /var/www/wopits.localhost/app/services/task/server-task.php
  # Run Swoole Websocket server
  /var/www/wopits.localhost/app/services/websocket/server-ws.php

} &> /dev/null

# Run cron
service cron start

echo
echo "###############################################################"
echo " WARNING: STILL IN BETA: any help will be welcome :-)          "
echo "          - For the moment, the docker DOES NOT SEND EMAILS! "
echo "###############################################################"

host=${WOPITS_HOST}
if [ ! -z "${WOPITS_HTTPS_PORT}" ]; then
  host="${host}:${WOPITS_HTTPS_PORT}"
fi

echo "-----------------------------------------------------------"
echo "- Now you can play on https://${host}"
echo "-----------------------------------------------------------"

# Run apache
rm -f /var/run/apache2/apache2.pid
/usr/sbin/apache2ctl -D FOREGROUND
