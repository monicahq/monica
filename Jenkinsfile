pipeline {
  agent none;
  stages {
    stage('build') {
      agent {
        docker {
          image 'monicahq/circleci-docker-centralperk'
          args '-v $HOME/.composer:/home/circleci/.composer -v $HOME/.cache:/home/circleci/.cache'
          label 'monica'
        }
      }
      steps {
        sh 'mkdir -p results/coverage'
        sh 'cp scripts/tests/.env.mysql .env'
        sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'
        sh 'yarn install --frozen-lockfile'
      }
    }
  }
}
