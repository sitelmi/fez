#!/bin/bash

set -xe

DEV_BASE=/var/app/current/.docker/development

echo -e "\n--- Bootstrapping ---"
cd ${DEV_BASE}

echo '127.0.0.1   dev-fez.library.uq.edu.au' >> /etc/hosts
# Comment out the below if you want to disable xdebug
#rm -f /etc/php.d/15-xdebug.ini
# Or uncomment this to use xdebug in cli processes
#echo 'xdebug.remote_host=10.26.99.102' >> /etc/php.d/15-xdebug.ini