pipeline {
  agent {
    docker {
      image 'monicahq/circleci-docker-centralperk'
      label 'monica'
    }
  }
  stages {
    stage('build') {
      steps {
        sh 'mkdir -p results/coverage'
        sh 'cp scripts/tests/.env.mysql .env'
        sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'
      }
    }
  }
}
