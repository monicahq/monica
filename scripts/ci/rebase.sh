#!/bin/sh

set -euov pipefail

GIT_COMMIT=$(git rev-parse --verify "HEAD^2" 2>/dev/null || echo $BUILD_SOURCEVERSION)

git reset --hard "$GIT_COMMIT"

if [[ $BUILD_SOURCEBRANCH == refs/tags/* ]]; then
  git checkout -q "$BUILD_SOURCEBRANCHNAME"
elif [ -n "$SYSTEM_PULLREQUEST_SOURCEBRANCH" ]; then
  git checkout -q -B "$SYSTEM_PULLREQUEST_SOURCEBRANCH"
fi

git reset --hard "$GIT_COMMIT"
