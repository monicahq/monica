#!/bin/sh

# set version of vagrant to use :
vagrantversion=2.0.2

mkdir -p $HOME/vagrant
pushd $HOME/vagrant > /dev/null

if [ ! -d "vagrant-$vagrantversion" ]; then
  mkdir -p "vagrant-$vagrantversion"
  pushd "vagrant-$vagrantversion" > /dev/null
  wget --quiet --continue https://releases.hashicorp.com/vagrant/${vagrantversion}/vagrant_${vagrantversion}_x86_64.deb
  popd > /dev/null
fi

sudo dpkg -i vagrant-$vagrantversion/vagrant_${vagrantversion}_x86_64.deb

popd > /dev/null
