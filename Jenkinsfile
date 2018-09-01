node('monica') {
  stage('Build') {
    docker.image('monicahq/circleci-docker-centralperk')
      .inside('-v $HOME/.composer:/home/circleci/.composer -v $HOME/.cache:/home/circleci/.cache') {
        checkout scm
        sh 'mkdir -p results/coverage'
        sh 'cp scripts/tests/.env.mysql .env'
        sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'
        sh 'yarn install --frozen-lockfile'
      }
  }
}