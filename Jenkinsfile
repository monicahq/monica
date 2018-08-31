pipeline {
  agent {label 'monica'}
  stages {
    stage('build') {
      steps {
        sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'
      }
    }
  }
}
