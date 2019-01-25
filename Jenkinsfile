pipeline {
  agent none
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
    stage('Prebuild') {
      agent { label 'monica' }
      when {
        beforeAgent true
        not { branch 'l10n_master*' }
      }
      steps {
        script {
          sh '''
            # Prebuild >
            mkdir -p $HOME/.yarn $HOME/.composer $HOME/.cache $HOME/.config
            touch $HOME/.yarnrc
          '''
          sh 'echo GIT_COMMIT=$GIT_COMMIT'
          sh 'echo ASSETS_EMAIL=$ASSETS_EMAIL'

          // Pull docker images
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

            // Install composer packages for cache
            sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'

            // Install node packages for cache 
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
          }
        }
      }
      post { always { cleanWs() } }
    }
    stage('Build and test') {
      when {
        beforeAgent true
        not { branch 'l10n_master*' }
      }
      failFast true
      parallel {
        stage('Rebuild assets') {
          agent { label 'monica' }
          when {
            beforeAgent true
            not { branch 'l10n_master*' }
          }
          steps {
            script {
              docker.image('monicahq/circleci-docker-centralperk')
              .inside("-v $HOME/.yarn:$HOME/.yarn -v $HOME/.yarnrc:$HOME/.yarnrc -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
                // Prepare environment
                sh '''
                  # Prepare environment >
                  mkdir -p results/coverage
                  cp scripts/ci/.env.jenkins.mysql .env
                '''

                // Composer
                sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs --no-dev'

                // Node.js
                sh 'yarn install --frozen-lockfile'

                // Update js and css assets eventually
                sh 'scripts/ci/update-assets.sh'
              }
            }
          }
          post { always { cleanWs() } }
        }
        stage ('Test php unit') {
          agent { label 'monica' }
          steps {
            script {
              docker.image('circleci/mysql:5.7-ram').withRun('--shm-size 2G -e "MYSQL_ALLOW_EMPTY_PASSWORD=yes" -e "MYSQL_ROOT_PASSWORD=" -e "DB_HOST=127.0.0.1" -e "DB_PORT=3306"') { c ->
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

                    // Fix unit file
                    sh 'sed -i "s%$WORKSPACE%!WORKSPACE!%g" results/junit/unit/*.xml'
                    junit 'results/junit/unit/*.xml'
                  }
                  finally {
                    stash includes: 'results/junit/', name: 'results1'
                    stash includes: 'results/*.xml', name: 'coverage1'
                    archiveArtifacts artifacts: 'results/junit/', fingerprint: true
                  }
                }
              }
            }
          }
          post { always { cleanWs() } }
        }
        stage ('Test browser') {
          agent { label 'monica' }
          steps {
            script {
              docker.image('circleci/mysql:5.7-ram').withRun('--shm-size 2G -e "MYSQL_ALLOW_EMPTY_PASSWORD=yes" -e "MYSQL_ROOT_PASSWORD=" -e "DB_HOST=127.0.0.1" -e "DB_PORT=3306"') { c ->
                docker.image('monicahq/circleci-docker-centralperk')
                .inside("--link ${c.id}:mysql -v /etc/passwd:/etc/passwd -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
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

                    sh 'sed -i "s%$WORKSPACE%!WORKSPACE!%g" results/junit/dusk/*.xml results/coverage2.xml'
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
          post { always { cleanWs() } }
        }
        /*
        stage ('Test e2e') {
          agent { label 'monica' }
          steps {
            script {
              docker.image('circleci/mysql:5.7-ram').withRun('--shm-size 2G -e "MYSQL_ALLOW_EMPTY_PASSWORD=yes" -e "MYSQL_ROOT_PASSWORD=" -e "DB_HOST=127.0.0.1" -e "DB_PORT=3306"') { c ->
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
        */
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
          post { always { cleanWs() } }
        }
      // end parallel
      }
    }
    stage('Reporting') {
      agent { label 'monica' }
      when {
        beforeAgent true
        not { branch 'l10n_master*' }
      }
      steps {
        script {
          docker.image('circleci/php:7.2-node')
          .inside("-v /etc/passwd:/etc/passwd -v $HOME/.yarn:$HOME/.yarn -v $HOME/.yarnrc:$HOME/.yarnrc -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
            unstash 'results1'
            unstash 'results2'
            //unstash 'results3'
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

            sh 'sed -i "s%!WORKSPACE!%$WORKSPACE%g" results/results.xml results/coverage*.xml'

            // Run sonar scanner
            //sh '''
            //  # Run sonar scanner >
            //  SONAR_RESULT=./results/results.xml SONAR_COVERAGE=$(find results -maxdepth 1 -name "coverage*.xml" | awk -vORS=, '{ print $1 }' | sed 's/,$//') scripts/tests/runsonar.sh
            //'''
          }
        }
      }
      post { always { cleanWs() } }
    }
    stage('Deploy') {
      when {
        anyOf {
          branch 'master'
          buildingTag()
        }
      }
      parallel {
        stage ('Deploy assets') {
          agent { label 'monica' }
          when {
            beforeAgent true
            anyOf {
              branch 'master'
              buildingTag()
            }
          }
          steps {
            script {
              sh 'make assets'
              //sh 'make push_bintray_assets'
            }
          }
          post { always { cleanWs() } }
        }
        stage ('Deploy dists') {
          agent { label 'monica' }
          when {
            beforeAgent true
            buildingTag()
          }
          steps {
            script {
              docker.image('monicahq/circleci-docker-centralperk')
              .inside("-v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache -v $HOME/.config:$HOME/.config") {
                // Composer
                sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs --no-dev'

                sh 'make dist'
                //sh 'make push_bintray_dist'
              }
            }
          }
          post { always { cleanWs() } }
        }
        stage ('Deploy docker for master') {
          agent { label 'monica' }
          when {
            beforeAgent true
            anyOf {
              branch 'master'
            }
          }
          steps {
            script {
              sh 'make docker_build'
              //sh '''
              //  # Publish docker image >
              //  echo $BINTRAY_APIKEY | docker login -u $BINTRAY_USER --password-stdin monicahq-docker-docker.bintray.io
              //  make docker_push_bintray
              //'''
            }
          }
          post { always { cleanWs() } }
        }
        stage ('Deploy docker') {
          agent { label 'monica' }
          when {
            beforeAgent true
            buildingTag()
          }
          steps {
            script {
              sh 'make docker_build'
              //sh '''
              //  # Publish docker image >
              //  echo $BINTRAY_APIKEY | docker login -u $BINTRAY_USER --password-stdin monicahq-docker-docker.bintray.io
              //  make docker_push_bintray
              //'''
              //sh '''
              //  # Publish docker image >
              //  echo $DOCKER_LOGIN | docker login -u $DOCKER_USER --password-stdin
              //  make docker_tag
              //  make docker_push
              //'''
              //sh '''
              //  # Notify microbadger >
              //  # @see https://microbadger.com/images/monicahq/monicahq
              //  test -s $MICROBADGER_WEBHOOK || curl -X POST $MICROBADGER_WEBHOOK
              //'''
            }
          }
          post { always { cleanWs() } }
        }
      }
    }
  }
}