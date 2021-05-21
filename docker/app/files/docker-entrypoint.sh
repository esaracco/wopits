#!/bin/bash

echo "########## PLEASE WAIT for wopits deployment..."

# Block output for the following commands
{
# Deploy wopits -> "-y" assume yes and "-M" do not minify
sudo -Eu wopits /home/wopits/app/deploy/deploy -y -M -edocker

# Run Swoole WS & Task services -> "-a" do not start apache
sudo -Eu wopits /var/www/wopits.localhost/app/deploy/bin/post-deploy.php -a

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
