#!/bin/bash

if [ "$CIRCLECI" == "true" ]; then
  if [[ ! -z $CIRCLE_PULL_REQUEST ]] ; then export CIRCLE_PR_NUMBER="${CIRCLE_PR_NUMBER:-${CIRCLE_PULL_REQUEST##*/}}" ; fi
  REPO=$CIRCLE_PROJECT_USERNAME/$CIRCLE_PROJECT_REPONAME
  BRANCH=${CIRCLE_BRANCH:-$CIRCLE_TAG}
  PR_NUMBER=${CIRCLE_PR_NUMBER:-false}
  BUILD=$CIRCLE_BUILD_NUM
  SHA1=$CIRCLE_SHA1
else
  REPO=$TRAVIS_REPO_SLUG
  BRANCH=${TRAVIS_PULL_REQUEST_BRANCH:-$TRAVIS_BRANCH}
  PR_NUMBER=$TRAVIS_PULL_REQUEST
  BUILD=$TRAVIS_BUILD_NUMBER
  SHA1=${TRAVIS_PULL_REQUEST_SHA:-$TRAVIS_COMMIT}
fi

set -euo pipefail

REPOSITORY_OWNER=monicahq/monica
SONAR_ORGANIZATION=monicahq

function installSonar {
  echo 'Setup sonar scanner'
  
  # set version of sonar scanner to use :
  sonarversion=3.1.0.1141

  mkdir -p $HOME/sonarscanner
  pushd $HOME/sonarscanner > /dev/null
  if [ ! -d "sonar-scanner-$sonarversion" ]; then
    java_path=$(which java || true)
    if [ -x "$java_path" ]; then
      wget --quiet --continue https://sonarsource.bintray.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-$sonarversion.zip
      unzip -q sonar-scanner-cli-$sonarversion.zip
      rm sonar-scanner-cli-$sonarversion.zip
    else    
      wget --quiet --continue https://sonarsource.bintray.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-$sonarversion-linux.zip
      unzip -q sonar-scanner-cli-$sonarversion-linux.zip
      rm sonar-scanner-cli-$sonarversion-linux.zip
      mv sonar-scanner-$sonarversion-linux sonar-scanner-$sonarversion
    fi
  fi
  export SONAR_SCANNER_HOME=$HOME/sonarscanner/sonar-scanner-$sonarversion
  export PATH=$SONAR_SCANNER_HOME/bin:$PATH
  popd > /dev/null
}

function CommonParams {
  extra=""
  if [ "$REPO" != "$REPOSITORY_OWNER" ]; then
    # Avoid forks to send reports to the same project
    project="${REPO/\//_}"
    extra="$extra -Dsonar.projectKey=monica:$project -Dsonar.projectName=$project"
  fi

  echo -Dsonar.host.url=$SONAR_HOST_URL \
       -Dsonar.organization=$SONAR_ORGANIZATION \
       -Dsonar.php.tests.reportPath=./results/result.xml \
       -Dsonar.php.coverage.reportPaths=./results/coverage.xml,./results/coverage2.xml \
       -Dsonar.analysis.buildNumber=$BUILD \
       -Dsonar.analysis.pipeline=$BUILD \
       $extra
}

function gitFetch {
  echo 'git fetch --unshallow'
  # Fetch all commit history so that SonarQube has exact blame information
  # for issue auto-assignment
  # This command can fail with "fatal: --unshallow on a complete repository does not make sense"
  # if there are not enough commits in the Git repository (even if Travis executed git clone --depth 50).
  # For this reason errors are ignored with "|| true"
  git fetch --unshallow || true
}

function getSonarlauncher {
  sonarlauncherversion=0.4.1
  mkdir -p ~/sonarlauncher
  pushd ~/sonarlauncher > /dev/null
  if [ ! -d "$sonarlauncherversion" ]; then
    echo "Download sonarlauncher"
    mkdir -p ~/sonarlauncher/$sonarlauncherversion
    curl -sSL https://github.com/monicahq/sonarlauncher/releases/download/$sonarlauncherversion/sonarlauncher.tar | tar x -C ~/sonarlauncher/$sonarlauncherversion
  fi
  popd > /dev/null
  cp ~/sonarlauncher/$sonarlauncherversion/sonarlauncher .
}

if [ -z "${SONAR_HOST_URL:-}" ]; then
  export SONAR_HOST_URL=https://sonarcloud.io
fi

if [ "$BRANCH" == "master" ] && [ "$PR_NUMBER" == "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '===================='
  echo 'SONAR:Analyze master'
  echo '===================='
  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.projectVersion=master \
    -Dsonar.analysis.sha1=$SHA1 \
    -Dsonar.analysis.repository=$REPO"

  echo sonar-scanner $SONAR_PARAMS
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN

elif [ -n "${BRANCH:-}" ] && [ "$PR_NUMBER" == "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '============================'
  echo 'SONAR:Analyze release branch'
  echo '============================'
  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.projectVersion=$(php artisan monica:getversion) \
    -Dsonar.analysis.sha1=$SHA1 \
    -Dsonar.analysis.repository=$REPO"
  
  echo sonar-scanner $SONAR_PARAMS
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN

elif [ "$PR_NUMBER" != "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then

  REPOS_VALUES=($(curl -H "Authorization: token $GITHUB_TOKEN" -sSL https://api.github.com/repos/$REPO/pulls/$PR_NUMBER | jq -r -c ".head.repo.full_name, .head.repo.owner.login, .base.ref, .head.ref"))

  PULL_REQUEST_BRANCH=
  PULL_REQUEST_REPOSITORY=${REPOS_VALUES[0]}
  PULL_REQUEST_USER=${REPOS_VALUES[1]}
  PULL_REQUEST_BASEBRANCH=${REPOS_VALUES[2]}
  PULL_REQUEST_HEADBRANCH=${REPOS_VALUES[3]}

  if [ -z "${PULL_REQUEST_REPOSITORY:-}" ] || [ "$PULL_REQUEST_REPOSITORY" == "null" ]; then
    echo 'Error with github api call'
    exit 1
  elif [ "$PULL_REQUEST_REPOSITORY" == "$REPOSITORY_OWNER" ]; then
    echo '==================================='
    echo 'SONAR:Analyze internal pull request'
    echo '==================================='
    PULL_REQUEST_BRANCH=$PULL_REQUEST_HEADBRANCH
  else
    echo '==================================='
    echo 'SONAR:Analyze external pull request'
    echo '==================================='
    echo External repository: $PULL_REQUEST_REPOSITORY
    PULL_REQUEST_BRANCH="$PULL_REQUEST_USER:$PULL_REQUEST_HEADBRANCH"
  fi

  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.analysis.sha1=$SHA1 \
    -Dsonar.analysis.prNumber=$PR_NUMBER \
    -Dsonar.analysis.repository=$REPO \
    -Dsonar.pullrequest.key=$PR_NUMBER \
    -Dsonar.pullrequest.base=$PULL_REQUEST_BASEBRANCH \
    -Dsonar.pullrequest.branch=$PULL_REQUEST_BRANCH \
    -Dsonar.pullrequest.github.id=$PR_NUMBER \
    -Dsonar.pullrequest.github.repository=$REPO"

  echo sonar-scanner $SONAR_PARAMS
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN

elif [ ! -x "sonarlauncher" ]; then

  getSonarlauncher
  echo '===== Run sonar launcher ====='
  ./sonarlauncher

fi
