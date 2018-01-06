#!/bin/bash
set -euo pipefail

function installSonar {
  echo 'Setup sonar scanner'

  # set version of sonar scanner to use :
  sonarversion=3.0.3.778

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
  if [ "$TRAVIS_REPO_SLUG" != "monicahq/monica" ]; then
    # Avoid forks to send reports to the same project
    project="${TRAVIS_REPO_SLUG/\//_}"
    extra="$extra -Dsonar.projectKey=monica:$project -Dsonar.projectName=$project"
  fi

  echo -Dsonar.host.url=$SONAR_HOST_URL \
       -Dsonar.organization=monicahq \
       -Dsonar.php.tests.reportPath=junit.xml \
       -Dsonar.php.coverage.reportPaths=clover.xml \
       -Dsonar.projectVersion=$(php artisan monica:getversion) \
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

if [ -z "${SONAR_HOST_URL:-}" ]; then
  export SONAR_HOST_URL=https://sonarcloud.io
fi

if [ "$TRAVIS_BRANCH" == "master" ] && [ "$TRAVIS_PULL_REQUEST" == "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '===================='
  echo 'SONAR:Analyze master'
  echo '===================='
  installSonar
  gitFetch

  echo sonar-scanner $(CommonParams) \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_COMMIT \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG

  $SONAR_SCANNER_HOME/bin/sonar-scanner $(CommonParams) \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_COMMIT \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG \
    -Dsonar.login=$SONAR_TOKEN

elif [ -n "${TRAVIS_BRANCH:-}" ] && [ "$TRAVIS_PULL_REQUEST" == "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '============================'
  echo 'SONAR:Analyze release branch'
  echo '============================'
  installSonar
  gitFetch

  echo sonar-scanner $(CommonParams) \
    -Dsonar.branch.name=$TRAVIS_BRANCH \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_COMMIT \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG
  
  $SONAR_SCANNER_HOME/bin/sonar-scanner $(CommonParams) \
    -Dsonar.branch.name=$TRAVIS_BRANCH \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_COMMIT \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG \
    -Dsonar.login=$SONAR_TOKEN

elif [ "$TRAVIS_PULL_REQUEST" != "false" ] && [ -n "${SONAR_TOKEN:-}" ]; then
  echo '==================================='
  echo 'SONAR:Analyze internal pull request'
  echo '==================================='
  installSonar
  gitFetch

  if [ -n "${GITHUB_TOKEN:-}" ]; then
    # analyse with GitHub token to add comment on the PR
    echo sonar-scanner $(CommonParams) \
      -Dsonar.analysis.mode=preview \
      -Dsonar.github.pullRequest=$TRAVIS_PULL_REQUEST \
      -Dsonar.github.repository=$TRAVIS_REPO_SLUG

    $SONAR_SCANNER_HOME/bin/sonar-scanner $(CommonParams) \
      -Dsonar.analysis.mode=preview \
      -Dsonar.github.pullRequest=$TRAVIS_PULL_REQUEST \
      -Dsonar.github.repository=$TRAVIS_REPO_SLUG \
      -Dsonar.github.oauth=$GITHUB_TOKEN \
      -Dsonar.login=$SONAR_TOKEN
  fi

  # analyse with GitHub token to add comment on the PR
  echo sonar-scanner $(CommonParams) \
    -Dsonar.branch.name=$TRAVIS_PULL_REQUEST_BRANCH \
    -Dsonar.branch.target=$TRAVIS_BRANCH \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_PULL_REQUEST_SHA \
    -Dsonar.analysis.prNumber=$TRAVIS_PULL_REQUEST \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG \
    -Dsonar.pullrequest.github.id=$TRAVIS_PULL_REQUEST \
    -Dsonar.pullrequest.github.repository=$TRAVIS_REPO_SLUG

  $SONAR_SCANNER_HOME/bin/sonar-scanner $(CommonParams) \
    -Dsonar.branch.name=$TRAVIS_PULL_REQUEST_BRANCH \
    -Dsonar.branch.target=$TRAVIS_BRANCH \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_PULL_REQUEST_SHA \
    -Dsonar.analysis.prNumber=$TRAVIS_PULL_REQUEST \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG \
    -Dsonar.pullrequest.github.id=$TRAVIS_PULL_REQUEST \
    -Dsonar.pullrequest.github.repository=$TRAVIS_REPO_SLUG \
    -Dsonar.login=$SONAR_TOKEN

else
  echo 'No analysis for external pull request .. yet'

  echo TRAVIS_REPO_SLUG=$TRAVIS_REPO_SLUG
  echo TRAVIS_BRANCH=$TRAVIS_BRANCH
  echo TRAVIS_BUILD_NUMBER=$TRAVIS_BUILD_NUMBER
  echo TRAVIS_COMMIT=$TRAVIS_COMMIT
  echo TRAVIS_PULL_REQUEST_SHA=$TRAVIS_PULL_REQUEST_SHA
  echo TRAVIS_PULL_REQUEST=$TRAVIS_PULL_REQUEST
  echo TRAVIS_PULL_REQUEST_BRANCH=$TRAVIS_PULL_REQUEST_BRANCH

fi
