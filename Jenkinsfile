node('monica') {

  def centralperk = docker.image('monicahq/circleci-docker-centralperk')
  centralperk.pull()
  
  checkout scm

  stage('Build') {
      centralperk.inside('-v /etc/passwd:/etc/passwd -v $HOME/.composer:$HOME/.composer -v $HOME/.cache:$HOME/.cache') {
        sh 'mkdir -p results/coverage'
        sh 'cp scripts/tests/.env.mysql .env'
        sh 'composer install --no-interaction --no-suggest --ignore-platform-reqs'
        sh 'yarn install --frozen-lockfile'
      }
  }
}