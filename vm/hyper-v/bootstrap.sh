#!/usr/bin/env bash

echo '#################### INSTALLATION for hyper-v'

sudo cp /vagrant/hyper-v/epel.repo /etc/yum.repos.d/
sudo yum remove php-common

source ../bootstrap.sh
 