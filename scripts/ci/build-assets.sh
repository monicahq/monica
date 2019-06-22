#!/bin/bash

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
source ${SELF_PATH}/fixsecrets.sh

#displayName: 'Update yarn lockfile'
TRAVIS=true TRAVIS_REPO_SLUG=$BUILD_REPOSITORY_NAME TRAVIS_BRANCH=$SYSTEM_PULLREQUEST_SOURCEBRANCH TRAVIS_PULL_REQUEST=false TRAVIS_JOB_NUMBER=1 GK_LOCK_YARN_OPTS="--ignore-engines" $(yarn global bin)/greenkeeper-lockfile-update

#displayName: 'yarn install'
yarn inst

#displayName: 'Upload yarn lockfile'
# SYSTEM_PULLREQUEST_PULLREQUESTID=$(test "$BUILD_SOURCEBRANCHNAME" != "master" -a "greenkeeperio-bot" = "`git log --format="%an" -n 1`" || echo '') $(yarn global bin)/greenkeeper-lockfile-upload
TRAVIS=true TRAVIS_REPO_SLUG=$BUILD_REPOSITORY_NAME TRAVIS_BRANCH=$SYSTEM_PULLREQUEST_SOURCEBRANCH TRAVIS_PULL_REQUEST=false TRAVIS_JOB_NUMBER=1 $(yarn global bin)/greenkeeper-lockfile-upload
cat gk-lockfile-git-push.err || true
rm -f gk-lockfile-git-push.err || true

# Update js and css assets eventually
yarn lint

${SELF_PATH}/update-assets.sh
