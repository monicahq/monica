#!/bin/bash
set -evuo pipefail

dockercomposeversion=1.18.0

mkdir -p ~/docker-compose
pushd ~/docker-compose > /dev/null
if [ ! -d "$dockercomposeversion" ]; then
  echo "Download docker-compose"
  mkdir -p $dockercomposeversion
  curl -sSL https://github.com/docker/compose/releases/download/$dockercomposeversion/docker-compose-`uname -s`-`uname -m` > $dockercomposeversion/docker-compose
fi

sudo cp $dockercomposeversion/docker-compose /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

popd > /dev/null
