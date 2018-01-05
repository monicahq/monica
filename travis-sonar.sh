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

installSonar

if [ -z "${SONAR_HOST_URL:-}" ]; then
    export SONAR_HOST_URL=https://sonarcloud.io
fi
export COMMON_PARAMS="\
    -Dsonar.host.url=$SONAR_HOST_URL \
    -Dsonar.login=$SONAR_TOKEN \
    -Dsonar.organization=monicahq \
    -Dsonar.php.tests.reportPath=junit.xml \
    -Dsonar.php.coverage.reportPaths=clover.xml \
    -Dsonar.projectVersion=$(php artisan monica:getversion)"

if [ "$TRAVIS_BRANCH" == "master" ] && [ "$TRAVIS_PULL_REQUEST" == "false" ]; then
  echo 'Analyze master'
  sonar-scanner $COMMON_PARAMS \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_COMMIT \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG

elif [ -n "${TRAVIS_BRANCH:-}" ] && [ "$TRAVIS_PULL_REQUEST" == "false" ]; then
  echo 'Analyze release branch'
  sonar-scanner $COMMON_PARAMS \
    -Dsonar.branch.name=$TRAVIS_BRANCH \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_COMMIT \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG

elif [ "$TRAVIS_PULL_REQUEST" != "false" ] && [ -n "${GITHUB_TOKEN:-}" ]; then
  echo 'Analyze internal pull request'
  sonar-scanner $COMMON_PARAMS \
    -Dsonar.branch.name=$TRAVIS_PULL_REQUEST_BRANCH \
    -Dsonar.branch.target=$TRAVIS_BRANCH \
    -Dsonar.analysis.buildNumber=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.pipeline=$TRAVIS_BUILD_NUMBER \
    -Dsonar.analysis.sha1=$TRAVIS_PULL_REQUEST_SHA \
    -Dsonar.analysis.prNumber=$TRAVIS_PULL_REQUEST \
    -Dsonar.analysis.repository=$TRAVIS_REPO_SLUG \
    -Dsonar.pullrequest.github.id=$TRAVIS_PULL_REQUEST \
    -Dsonar.pullrequest.github.repository=$TRAVIS_REPO_SLUG

else
  echo 'No analysis for external pull request .. yet'

fi
