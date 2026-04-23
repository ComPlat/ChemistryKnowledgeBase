#!/usr/bin/env sh

sudo  apt-get update
# Install latest chrome dev package, which installs the necessary libs to
# make the bundled version of Chromium that Puppeteer installs work.
sudo apt-get install -y wget xvfb --no-install-recommends
sudo wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
sudo sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list'
sudo apt-get update
sudo apt-get install -y google-chrome-stable --no-install-recommends

sudo mkdir /opt/downloadPDF
sudo cd /opt/downloadPDF
sudo wget https://downloads.diqa-pm.com/free/test/downloadPDF.jar
