#!/bin/bash

if [ "$CIRCLECI" == "true" ]; then
  if [[ ! -z $CIRCLE_PULL_REQUEST ]] ; then
    CIRCLE_PR_NUMBER="${CIRCLE_PR_NUMBER:-${CIRCLE_PULL_REQUEST##*/}}"
    REPO=$CIRCLE_PULL_REQUEST
    REPO=${REPO##https://github.com/}
    REPO=${REPO%%/pull/$CIRCLE_PR_NUMBER}
  else
    REPO=$CIRCLE_PROJECT_USERNAME/$CIRCLE_PROJECT_REPONAME
  fi
  BRANCH=${CIRCLE_BRANCH:-$CIRCLE_TAG}
  PR_NUMBER=${CIRCLE_PR_NUMBER:-false}
elif [ "$TRAVIS" == "true" ]; then
  REPO=$TRAVIS_REPO_SLUG
  BRANCH=${TRAVIS_PULL_REQUEST_BRANCH:-$TRAVIS_BRANCH}
  PR_NUMBER=$TRAVIS_PULL_REQUEST
elif [ "$TF_BUILD" == "True" ]; then
  REPO=$BUILD_REPOSITORY_NAME
  BRANCH=${SYSTEM_PULLREQUEST_SOURCEBRANCH:-$BUILD_SOURCEBRANCHNAME}
  PR_NUMBER=${SYSTEM_PULLREQUEST_PULLREQUESTNUMBER:-false}
elif [[ -n $BUILD_NUMBER ]]; then
  echo "CHANGE_ID=$CHANGE_ID"
  echo "CHANGE_URL=$CHANGE_URL"
  REPO=${CHANGE_URL##https://github.com/}
  if [[ ! -z $CHANGE_ID ]] ; then
    REPO=${REPO%%/pull/$CHANGE_ID}
  fi
  PR_NUMBER=${CHANGE_ID:-false}
  BRANCH=$BRANCH_NAME
fi

REPOSITORY_OWNER=monicahq/monica

set -euo pipefail

# Update assets
echo -e "\033[1;32m# Build assets ...\033[0;37m"
echo -e "\033[1;36mphp artisan lang:generate\033[0;37m"
php artisan lang:generate
echo ""
echo -e "\033[1;36myarn run production\033[0;37m"
yarn run production
echo ""

# Check if there is zero update needed
status=$(git status --porcelain)
if [ "$status" = "" ]; then
  echo "Nothing to push, already up to date."
  exit 0;
fi
echo "Waiting modifications:"
echo $status

# Add files
git add public/mix-manifest.json
git add public/js/*
git add public/css/*
git add public/fonts/*

# Commit
if [ -z "${ASSETS_USERNAME:-}" ]; then
  #No username
  echo -e "\033[0;31mMonica asset are not up to date.\033[0;37m"
  echo "Please update the Monica assets yourself by running:"
  echo " ~ php artisan lang:generate"
  echo " ~ yarn run production"
  exit 2
fi
git config user.email $ASSETS_EMAIL
git config user.name $ASSETS_USERNAME
git commit -m "chore(assets): Update assets"

# Push
if [ "$BRANCH" == "master" ] && [ "$PR_NUMBER" == "false" ]; then
  echo -e "\033[0;31mmaster is not up to date, but we can't update it directly...\033[0;37m"
  exit 0

elif [ -n "${ASSETS_GITHUB_TOKEN:-}" ]; then
  REPOS_VALUES=($(curl -H "Authorization: token $ASSETS_GITHUB_TOKEN" -sSL https://api.github.com/repos/$REPO/pulls/$PR_NUMBER | jq -r -c ".head.repo.full_name, .head.ref"))

  PULL_REQUEST_BRANCH=
  PULL_REQUEST_REPOSITORY=${REPOS_VALUES[0]}
  PULL_REQUEST_HEADBRANCH=${REPOS_VALUES[1]}

  if [ -z "${PULL_REQUEST_REPOSITORY:-}" ] || [ "$PULL_REQUEST_REPOSITORY" == "null" ]; then
    echo -e "\033[0;31mError with github api call\033[0;37m"
    exit 1
  elif [ "$PULL_REQUEST_REPOSITORY" == "$REPOSITORY_OWNER" ]; then
    PULL_REQUEST_BRANCH=$PULL_REQUEST_HEADBRANCH
  else
    echo -e "\033[0;31mMonica asset are not up to date.\033[0;37m"
    echo "We can't commit in $PULL_REQUEST_REPOSITORY to update them directly."
    echo "Please update the Monica assets yourself by running:"
    echo " ~ php artisan lang:generate"
    echo " ~ yarn run production"
    exit 2
  fi

  echo "Pushing files to $PULL_REQUEST_BRANCH branch ..."
  remote=monica-origin
  remoteurl="https://$ASSETS_USERNAME:$ASSETS_GITHUB_TOKEN@github.com/$REPO"
  git remote remove $remote || true
  git remote add -f $remote $remoteurl || true
  git push $remote HEAD:$PULL_REQUEST_BRANCH

  # Exit with error to stop the current build
  echo "...pushed files successfully."
  echo "Exit with error to stop the current build."
  exit -1
fi