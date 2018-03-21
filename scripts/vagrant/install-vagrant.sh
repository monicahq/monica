#!/bin/bash
set -euo pipefail

# set version of vagrant to use :
vagrantversion=2.0.3

mkdir -p $HOME/vagrant
pushd $HOME/vagrant > /dev/null

if [ ! -d "vagrant-$vagrantversion" ]; then
  mkdir -p "vagrant-$vagrantversion"
  pushd "vagrant-$vagrantversion" > /dev/null

  curl -Os https://releases.hashicorp.com/vagrant/${vagrantversion}/vagrant_${vagrantversion}_x86_64.deb

  curl -Os https://releases.hashicorp.com/vagrant/${vagrantversion}/vagrant_${vagrantversion}_SHA256SUMS
  curl -Os https://releases.hashicorp.com/vagrant/${vagrantversion}/vagrant_${vagrantversion}_SHA256SUMS.sig
  gpg --keyserver keys.gnupg.net --recv-keys 51852D87348FFC4C

  verif=0
  if gpg --quiet --verify vagrant_${vagrantversion}_SHA256SUMS.sig vagrant_${vagrantversion}_SHA256SUMS 2>/dev/null; then
    if grep vagrant_${vagrantversion}_x86_64.deb vagrant_${vagrantversion}_SHA256SUMS | shasum -a 256 -c - 2>/dev/null >/dev/null; then
      verif=1
    else
      echo ERROR: checksum don\'t match
    fi
  else
    echo ERROR: signature don\'t match
  fi

  rm -f vagrant_${vagrantversion}_SHA256SUMS*

  popd > /dev/null

  if [ "$verif" = 0 ]; then
    rm -rf vagrant-$vagrantversion
  fi
fi

if [ -f vagrant-$vagrantversion/vagrant_${vagrantversion}_x86_64.deb ]; then
  sudo dpkg -i vagrant-$vagrantversion/vagrant_${vagrantversion}_x86_64.deb
fi

popd > /dev/null
