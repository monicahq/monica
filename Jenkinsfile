node('monica') {

  def workspace = pwd()
  def centralperk = docker.image('monicahq/circleci-docker-centralperk')
  centralperk.pull()
  
  checkout scm

  environment {
    ASSETS_EMAIL = credentials('ASSETS_EMAIL')
    ASSETS_GITHUB_TOKEN = credentials('ASSETS_GITHUB_TOKEN')
    ASSETS_USERNAME = credentials('ASSETS_USERNAME')
    BINTRAY_APIKEY = credentials('BINTRAY_APIKEY')
    BINTRAY_USER = credentials('BINTRAY_USER')
    DOCKER_LOGIN = credentials('DOCKER_LOGIN')
    DOCKER_USER = credentials('DOCKER_USER')
    GH_TOKEN = credentials('GH_TOKEN')
    GITHUB_TOKEN = credentials('GITHUB_TOKEN')
    MICROBADGER_WEBHOOK = credentials('MICROBADGER_WEBHOOK')
    SONAR_TOKEN = credentials('SONAR_TOKEN')
    SONAR_VERSION = credentials('SONAR_VERSION')
  }
  stage('Build') {
    centralperk.inside("-v /etc/passwd:/etc/passwd -v $HOME/.yarn:$HOME/.yarn -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
      checkout scm

      // Prepare environment
      sh '''
        mkdir -p results/coverage
        cp scripts/tests/.env.mysql .env
        yarn global add greenkeeper-lockfile@1
      '''

      // Composer
      sh '''
        composer install --no-interaction --no-suggest --ignore-platform-reqs
      '''

      // Node.js
      sh '''
        CIRCLE_PREVIOUS_BUILD_NUM=$(test "`git rev-parse --abbrev-ref HEAD`" != "master" -a "greenkeeper[bot]" = "`git log --format="%an" -n 1`" || echo false) CI_PULL_REQUEST="" $(yarn global bin)/greenkeeper-lockfile-update
        yarn install --frozen-lockfile
        CIRCLE_PREVIOUS_BUILD_NUM=$(test "`git rev-parse --abbrev-ref HEAD`" != "master" -a "greenkeeperio-bot" = "`git log --format="%an" -n 1`" || echo false) $(yarn global bin)/greenkeeper-lockfile-upload
        cat gk-lockfile-git-push.err || true
        rm -f gk-lockfile-git-push.err || true
      '''

      // Update js and css assets eventually
      //sh 'scripts/ci/update-assets.sh'
      stash includes: 'vendor/', name: 'composer'
    }
  }
  stage('Tests') {
    parralel {
      stage('tests-7.2') {
        docker.image('circleci/mysql:5.7-ram')
        .withRun('-e "MYSQL_ALLOW_EMPTY_PASSWORD=yes" -e "MYSQL_ROOT_PASSWORD="') { c ->
          centralperk.inside("--link ${c.id}:mysql -v /etc/passwd:/etc/passwd") {
            try {
              checkout scm

              unstash 'composer'
              // Prepare environment
              sh '''
                mkdir -p results/coverage
                cp scripts/tests/.env.mysql .env
              '''

              // Remove xdebug
              sh 'rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini'

              // Prepare database
              sh '''
                dockerize -wait tcp://127.0.0.1:3306 -timeout 60s
                mysql --protocol=tcp -u root -e "CREATE DATABASE IF NOT EXISTS monica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
                php artisan migrate --no-interaction -vvv
              '''

              // Seed database
              sh 'php artisan db:seed --no-interaction -vvv'

              // Run unit tests
              sh 'phpdbg -dmemory_limit=4G -qrr vendor/bin/phpunit -c phpunit.xml --log-junit ./results/junit/unit/results.xml --coverage-clover ./results/coverage.xml'
            }
            finally {
              junit 'results/junit/*.xml'
              stash includes: 'results/junit/', name: 'results/junit' 
            }
          }
        }
      }
    }
  }
  post {
    always {
      cleanWs()
    }
  }
}