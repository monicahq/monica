#!/bin/bash
# Rebase source on top of based branch

set -exvo pipefail

export GIT_COMMIT=$(git rev-parse --verify "HEAD^2" 2>/dev/null || echo $BUILD_SOURCEVERSION)
echo -e "GIT_COMMIT=$GIT_COMMIT"
echo -e "##vso[task.setvariable variable=GIT_COMMIT]$GIT_COMMIT"

git config checkout.defaultRemote origin
git reset --hard "$GIT_COMMIT"

if [[ $BUILD_SOURCEBRANCH == refs/tags/* ]]; then
  git checkout -q "$BUILD_SOURCEBRANCHNAME"
elif [ -n "$SYSTEM_PULLREQUEST_SOURCEBRANCH" ]; then
  git checkout -q -B "$SYSTEM_PULLREQUEST_SOURCEBRANCH"
fi

#git reset --hard "$GIT_COMMIT"
git log -n1
