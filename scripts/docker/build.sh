#!/bin/bash

set -eo pipefail

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
source $SELF_PATH/../realpath.sh
ROOT=$(realpath $SELF_PATH/../..)

version=$(git --git-dir $ROOT/.git describe --abbrev=0 --tags | sed 's/^v//')

commit=$1
if [ "$commit" == "--skip-build" ]; then
  shift
  tag=$commit
  commit=$1
fi

if [ -z "$commit"  ]; then
  commit=$(git --git-dir $ROOT/.git log --pretty="%H" -n1 HEAD)
  release=$(git --git-dir $ROOT/.git log --pretty="%h" -n1 HEAD)
else
  shift
  release=$(git --git-dir $ROOT/.git describe --abbrev=0 --tags --exact-match $commit 2>/dev/null || git --git-dir $ROOT/.git log --pretty="%h" -n1 $commit)
fi

if [ -z "$tag" ]; then
  tag=${1:-monica-dev}
fi

echo Version
echo -n "$version" | tee config/.version

echo -e "\nCommit"
echo -n "$commit" | tee config/.commit

echo -e "\nRelease"
echo -n "$release" | tee config/.release

echo -e "\n"

# BUILD
composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader --no-dev --working-dir=$ROOT
yarn --cwd $ROOT run inst
yarn --cwd $ROOT run production

# DOCKER BUILD
if [ "$tag" != "--skip-build" ]; then
  docker build -t $tag -f $SELF_PATH/Dockerfile $ROOT
  rm -f config/.{version,commit,release}
fi
