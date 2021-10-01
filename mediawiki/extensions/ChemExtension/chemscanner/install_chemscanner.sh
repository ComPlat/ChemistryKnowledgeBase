#!/usr/bin/env bash
# https://github.com/ComPlat/chem_scanner

## Rocky
#http://www.herongyang.com/Molecule/RDKit-Build-from-Source-Code-on-CentOS-System.html

sudo dnf install gcc-c++
sudo dnf install cmake
sudo dnf install tk-devel
sudo dnf install readline-devel
sudo dnf install sqlite-devel
sudo dnf install boost-devel
sudo dnf install unzip
sudo dnf install wget

wget https://github.com/rdkit/rdkit/archive/refs/heads/master.zip
unzip master.zip
# verz. umbennen nach rdkit
mkdir ~/rdkit/build
cd ~/rdkit/build
cmake -DRDK_BUILD_PYTHON_WRAPPERS=OFF ..
make
make install

# ruby
sudo dnf install ruby
sudo dnf install ruby-devel
sudo dnf install redhat-rpm-config

gem install bundler
gem install rdkit_chem
gem install chem_scanner
