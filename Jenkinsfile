pipeline {
  agent { label 'monica' }
  environment {
    ASSETS_EMAIL = credentials('ASSETS_EMAIL')
    ASSETS_GITHUB_TOKEN = credentials('ASSETS_GITHUB_TOKEN')
    ASSETS_USERNAME = credentials('ASSETS_USERNAME')
    BINTRAY_APIKEY = credentials('BINTRAY_APIKEY')
    BINTRAY_USER = credentials('BINTRAY_USER')
    CYPRESS_RECORD_KEY = credentials('CYPRESS_RECORD_KEY')
    DOCKER_LOGIN = credentials('DOCKER_LOGIN')
    DOCKER_USER = credentials('DOCKER_USER')
    GH_TOKEN = credentials('GH_TOKEN')
    GITHUB_TOKEN = credentials('GITHUB_TOKEN')
    MICROBADGER_WEBHOOK = credentials('MICROBADGER_WEBHOOK')
    SONAR_TOKEN = credentials('SONAR_TOKEN')
    SONAR_VERSION = credentials('SONAR_VERSION')
  }
  stages {
    stage('Build') {
      agent { label 'monica' }
      steps {
        script {
          sh '''
            # Prebuild   
            mkdir -p $HOME/.yarn $HOME/.composer $HOME/.cache $HOME/.config
            touch $HOME/.yarnrc
          '''
          def centralperk = docker.image('monicahq/circleci-docker-centralperk')
          centralperk.pull()
          def mysql = docker.image('circleci/mysql:5.7-ram')
          mysql.pull()
          centralperk.inside("-v $HOME/.yarn:$HOME/.yarn -v $HOME/.yarnrc:$HOME/.yarnrc -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
            // Prepare environment
            sh '''
              # Prepare environment >
              mkdir -p results/coverage
              cp scripts/ci/.env.jenkins.mysql .env
              yarn global add greenkeeper-lockfile@1
            '''

            // Composer
            sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'

            // Node.js
            sh '''
              # greenkeeper-lockfile-update >
              CIRCLE_PREVIOUS_BUILD_NUM=$(test "`git rev-parse --abbrev-ref HEAD`" != "master" -a "greenkeeper[bot]" = "`git log --format="%an" -n 1`" || echo false) CI_PULL_REQUEST="" $(yarn global bin)/greenkeeper-lockfile-update
            '''
            sh 'yarn install --frozen-lockfile'
            sh '''
              # greenkeeper-lockfile-upload >
              CIRCLE_PREVIOUS_BUILD_NUM=$(test "`git rev-parse --abbrev-ref HEAD`" != "master" -a "greenkeeperio-bot" = "`git log --format="%an" -n 1`" || echo false) $(yarn global bin)/greenkeeper-lockfile-upload
              cat gk-lockfile-git-push.err || true
              rm -f gk-lockfile-git-push.err || true
            '''

            // Update js and css assets eventually
            sh 'scripts/ci/update-assets.sh'
          }
        }
      }
    }
    stage('Run Tests') {
      parallel {
        stage ('Test php unit') {
          agent { label 'monica' }
          steps {
            script {
              docker.image('circleci/mysql:5.7-ram').withRun('--shm-size 2G -e "MYSQL_ALLOW_EMPTY_PASSWORD=yes" -e "MYSQL_ROOT_PASSWORD=" -e "DB_HOST=127.0.0.1" -e "DB_PORT=3306"') { c ->
                sh "docker logs ${c.id}"

                docker.image('monicahq/circleci-docker-centralperk').inside("--link ${c.id}:mysql -v /etc/passwd:/etc/passwd -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
                  try {
                    // Prepare environment
                    sh '''
                      # Prepare environment >
                      mkdir -p results/coverage
                      cp scripts/ci/.env.jenkins.mysql .env
                    '''

                    // Composer
                    sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'

                    // Remove xdebug
                    sh 'sudo rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini || true'

                    // Prepare database
                    sh '''
                      # Prepare database >
                      dockerize -wait tcp://mysql:3306 -timeout 60s
                      mysql --protocol=tcp -u root -h mysql -e "CREATE DATABASE IF NOT EXISTS monica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
                      php artisan migrate --no-interaction -vvv
                    '''

                    // Seed database
                    sh 'php artisan db:seed --no-interaction -vvv'

                    // Run unit tests
                    sh 'phpdbg -dmemory_limit=4G -qrr vendor/bin/phpunit -c phpunit.xml --log-junit ./results/junit/unit/results.xml --coverage-clover ./results/coverage.xml'
                    junit 'results/junit/unit/*.xml'
                  }
                  finally {
                    stash includes: 'results/junit/', name: 'results1'
                    stash includes: 'results/*.xml', name: 'coverage2'
                    archiveArtifacts artifacts: 'results/junit/', fingerprint: true
                  }
                }
              }
            }
          }
        }
        stage ('Test browser') {
          agent { label 'monica' }
          steps {
            script {
              docker.image('circleci/mysql:5.7-ram').withRun('--shm-size 2G -e "MYSQL_ALLOW_EMPTY_PASSWORD=yes" -e "MYSQL_ROOT_PASSWORD=" -e "DB_HOST=127.0.0.1" -e "DB_PORT=3306"') { c ->
                sh "docker logs ${c.id}"

                docker.image('monicahq/circleci-docker-centralperk').inside("--link ${c.id}:mysql -v /etc/passwd:/etc/passwd -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
                  try {
                    // Prepare environment
                    sh '''
                      # Prepare environment >
                      mkdir -p results/coverage
                      cp scripts/ci/.env.jenkins.mysql .env
                    '''

                    // Composer
                    sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'

                    // Prepare database
                    sh '''
                      # Prepare database >
                      dockerize -wait tcp://mysql:3306 -timeout 60s
                      mysql --protocol=tcp -u root -h mysql -e "CREATE DATABASE IF NOT EXISTS monica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
                      php artisan migrate --no-interaction -vvv
                    '''

                    // Seed database
                    sh 'php artisan db:seed --no-interaction -vvv'

                    // Run selenium chromedriver
                    sh 'JENKINS_NODE_COOKIE=x vendor/bin/chromedriver &'

                    // Run http server
                    sh 'JENKINS_NODE_COOKIE=x php -S localhost:8000 -t public scripts/tests/server-cc.php 2>/dev/null &'

                    // Wait for http server
                    sh 'dockerize -wait tcp://localhost:8000 -timeout 60s'
                    // Run browser tests
                    sh 'php artisan dusk --log-junit results/junit/dusk/results.xml'
                    // Fix coverage
                    sh 'vendor/bin/phpcov merge --clover=results/coverage2.xml results/coverage/'
                    sh 'rm -rf results/coverage'

                    junit 'results/junit/dusk/*.xml'
                  }
                  finally {
                    stash includes: 'results/junit/', name: 'results2'
                    stash includes: 'results/*.xml', name: 'coverage2'
                    archiveArtifacts artifacts: 'results/junit/', fingerprint: true
                    //archiveArtifacts artifacts: 'tests/Browser/screenshots/', fingerprint: true
                  }
                }
              }
            }
          }
        }
        stage ('Test e2e') {
          agent { label 'monica' }
          steps {
            script {
              docker.image('circleci/mysql:5.7-ram').withRun('--shm-size 2G -e "MYSQL_ALLOW_EMPTY_PASSWORD=yes" -e "MYSQL_ROOT_PASSWORD=" -e "DB_HOST=127.0.0.1" -e "DB_PORT=3306"') { c ->
                sh "docker logs ${c.id}"

                docker.image('monicahq/circleci-docker-centralperk').inside("--link ${c.id}:mysql -v /etc/passwd:/etc/passwd -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
                  try {
                    // Prepare environment
                    sh '''
                      # Prepare environment >
                      mkdir -p results/coverage
                      cp scripts/ci/.env.jenkins.mysql .env
                    '''

                    // Composer
                    sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'

                    // Node.js
                    sh 'yarn install --frozen-lockfile'

                    // Prepare database
                    sh '''
                      # Prepare database >
                      dockerize -wait tcp://mysql:3306 -timeout 60s
                      mysql --protocol=tcp -u root -h mysql -e "CREATE DATABASE IF NOT EXISTS monica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
                      php artisan migrate --no-interaction -vvv
                    '''
                    sh '''
                      # Dump database >
                      mysqldump -u root -h mysql -P 3306 monica > monicadump.sql
                    '''

                    // Run http server
                    //sh 'JENKINS_NODE_COOKIE=x php -S localhost:8000 -t public scripts/tests/server-cc.php 2>/dev/null &'
                    sh 'JENKINS_NODE_COOKIE=x php artisan serve 2>/dev/null &'

                    // Wait for http server
                    sh 'dockerize -wait tcp://localhost:8000 -timeout 60s'

                    // Run browser tests
                    sh '''
                      # Run browser tests (cypress) >
                      $(yarn bin)/cypress run --config "baseUrl=http://localhost:8000" --record --reporter mocha-multi-reporters --reporter-options configFile=scripts/ci/cypressmocha.json
                    '''

                    // Fix coverage
                    //sh 'vendor/bin/phpcov merge --clover=results/coverage3.xml results/coverage/'
                    //sh 'rm -rf results/coverage'

                    junit 'results/junit/dusk/*.xml'
                  }
                  finally {
                    stash includes: 'results/junit/', name: 'results3'
                    //stash includes: 'results/*.xml', name: 'coverage3'
                    archiveArtifacts artifacts: 'results/junit/', fingerprint: true
                    //archiveArtifacts artifacts: 'tests/cypress/screenshots/', fingerprint: true
                  }
                }
              }
            }
          }
        }
        stage ('Psalm') {
          agent { label 'monica' }
          steps {
            script {
              docker.image('monicahq/circleci-docker-centralperk')
              .inside("-v /etc/passwd:/etc/passwd -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
                // Prepare environment
                sh '''
                  # Prepare environment >
                  mkdir -p results/coverage
                  cp scripts/ci/.env.jenkins.mysql .env
                '''

                // Composer
                sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'

                // Run psalm
                sh 'vendor/bin/psalm --show-info=false'
              }
            }
          }
        }
      // end parallel
      }
    }
    stage('Reporting') {
      agent { label 'monica' }
      steps {
        script {
          docker.image('circleci/php:7.2-node')
          .inside("-v /etc/passwd:/etc/passwd -v $HOME/.yarn:$HOME/.yarn -v $HOME/.yarnrc:$HOME/.yarnrc -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
            unstash 'results1'
            unstash 'results2'
            unstash 'results3'
            unstash 'coverage1'
            unstash 'coverage2'
            //unstash 'coverage3'

            // Prepare environment
            sh '''
              # Prepare environment >
              mkdir -p results/coverage
              cp scripts/ci/.env.jenkins.mysql .env
            '''

            // Composer
            sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'

            // Merge junit files
            sh '''
              # Merge junit files >
              yarn global add junit-merge
              $(yarn global bin)/junit-merge --recursive --dir results/junit --out results/results.xml
            '''

            // Run sonar scanner
            //sh '''
            //  # Run sonar scanner >
            //  SONAR_RESULT=./results/results.xml SONAR_COVERAGE=./results/coverage.xml,./results/coverage2.xml,./results/coverage3.xml scripts/tests/runsonar.sh
            //'''
            sh '''
              # Run sonar scanner >
              SONAR_RESULT=./results/results.xml SONAR_COVERAGE=./results/coverage.xml,./results/coverage2.xml scripts/tests/runsonar.sh
            '''
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