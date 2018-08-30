pipeline {
  agent {
    docker {
      image 'monicahq/circleci-docker-centralperk'
    }

  }
  stages {
    stage('build') {
      steps {
        sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'
      }
    }
  }
}