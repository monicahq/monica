#!/bin/bash
set -euo pipefail

REPOSITORY_OWNER=monicahq/monica

function installSonar {
  echo 'Setup sonar scanner'

  # set version of sonar scanner to use :
  sonarversion=3.1.0.1141

  mkdir -p $HOME/sonarscanner
  pushd $HOME/sonarscanner > /dev/null
  if [ ! -d "sonar-scanner-$sonarversion" ]; then
    wget --quiet --continue https://sonarsource.bintray.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-$sonarversion.zip
    unzip -q sonar-scanner-cli-$sonarversion.zip
    rm sonar-scanner-cli-$sonarversion.zip
  fi
  export SONAR_SCANNER_HOME=$HOME/sonarscanner/sonar-scanner-$sonarversion
  export PATH=$SONAR_SCANNER_HOME/bin:$PATH
  popd > /dev/null
}

function CommonParams {
  extra=""
  if [ "$TRAVIS_REPO_SLUG" != "$REPOSITORY_OWNER" ]; then
    # Avoid forks to send reports to the same project
    project="${TRAVIS_REPO_SLUG/\//_}"
    extra="$extra -Dsonar.projectKey=monica:$project -Dsonar.projectName=$project"
  fi

  echo -Dsonar.host.url=$SONAR_HOST_URL \
       -Dsonar.organization=monicahq \
       -Dsonar.php.tests.reportPath=./results/junit.xml \
       -Dsonar.php.coverage.reportPaths=./results/coverage.xml,./results/coverage2.xml \
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
  sonarlauncherversion=0.3.0
  mkdir -p ~/sonarlauncher
  pushd ~/sonarlauncher > /dev/null
  if [ ! -d "$sonarlauncherversion" ]; then
    echo "Download travis-sonarlauncher"
    mkdir -p ~/sonarlauncher/$sonarlauncherversion
    curl -sSL https://github.com/monicahq/sonarlauncher/releases/download/$sonarlauncherversion/travis-sonarlauncher.tar | tar x -C ~/sonarlauncher/$sonarlauncherversion
  fi
  popd > /dev/null
  cp ~/sonarlauncher/$sonarlauncherversion/travis-sonarlauncher .
}

if [ -z "${SONAR_HOST_URL:-}" ]; then
  export SONAR_HOST_URL=https://sonarcloud.io
fi

if [ "$TRAVIS_BRANCH" == "master" ] && [ "$TRAVIS_PULL_REQUEST" == "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '===================='
  echo 'SONAR:Analyze master'
  echo '===================='
  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.projectVersion=master \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_COMMIT \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG"

  echo sonar-scanner $SONAR_PARAMS
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN

elif [ -n "${TRAVIS_BRANCH:-}" ] && [ "$TRAVIS_PULL_REQUEST" == "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '============================'
  echo 'SONAR:Analyze release branch'
  echo '============================'
  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.projectVersion=$(php artisan monica:getversion) \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_COMMIT \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG"
  
  echo sonar-scanner $SONAR_PARAMS
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN

elif [ "$TRAVIS_PULL_REQUEST" != "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then

  REPOS_VALUES=($(curl -sSL https://api.github.com/repos/$TRAVIS_REPO_SLUG/pulls/$TRAVIS_PULL_REQUEST | jq -r -c ".head.repo.full_name, .head.repo.owner.login"))

  PULL_REQUEST_BRANCH=
  PULL_REQUEST_REPOSITORY=${REPOS_VALUES[0]}
  PULL_REQUEST_USER=${REPOS_VALUES[1]}

  if [ -z "${PULL_REQUEST_REPOSITORY:-}" ] || [ "$PULL_REQUEST_REPOSITORY" == "null" ]; then
    echo 'Error with github api call'
    exit 1
  elif [ "$PULL_REQUEST_REPOSITORY" == "$REPOSITORY_OWNER" ]; then
    echo '==================================='
    echo 'SONAR:Analyze internal pull request'
    echo '==================================='
    PULL_REQUEST_BRANCH=$TRAVIS_PULL_REQUEST_BRANCH
  else
    echo '==================================='
    echo 'SONAR:Analyze external pull request'
    echo '==================================='
    echo External repository: $PULL_REQUEST_REPOSITORY
    PULL_REQUEST_BRANCH="PR${TRAVIS_PULL_REQUEST}_($PULL_REQUEST_USER)_$TRAVIS_PULL_REQUEST_BRANCH"
  fi

  installSonar
  gitFetch

  SONAR_PARAMS="$(CommonParams) \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_PULL_REQUEST_SHA \
    -Dsonar.analysis.prNumber=$TRAVIS_PULL_REQUEST \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG \
    -Dsonar.pullrequest.key=$TRAVIS_PULL_REQUEST \
    -Dsonar.pullrequest.base=$TRAVIS_BRANCH \
    -Dsonar.pullrequest.branch=$PULL_REQUEST_BRANCH \
    -Dsonar.pullrequest.github.id=$TRAVIS_PULL_REQUEST \
    -Dsonar.pullrequest.github.repository=$TRAVIS_REPO_SLUG"

  echo sonar-scanner $SONAR_PARAMS
  $SONAR_SCANNER_HOME/bin/sonar-scanner $SONAR_PARAMS -Dsonar.login=$SONAR_TOKEN

elif [ ! -a "travis-sonarlauncher" ]; then

  getSonarlauncher
  ./travis-sonarlauncher

fi
