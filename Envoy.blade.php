// See Envoy doc: https://laravel.com/docs/5.8/envoy
// 
// Configure with: `envoy run git-config --host=my-host --site=site-conf`
// Deploy with: `envoy run deploy`

@servers(['local' => 'localhost', 'web' => $web ])

@setup
    $commit = exec('git log --pretty="%H" -n1 HEAD');
    //$version = exec('php artisan monica:getversion');
    $release = exec('git log --pretty="%h" -n1 HEAD');
@endsetup

@story('deploy')
    pull
    sentry-file
    push
@endstory

@task('push', ['on' => 'local'])
    git push -u deploy {{ $branch ?? 'master' }} --force
@endtask

@task('pull', ['on' => 'local'])
    git fetch origin
    git checkout {{ $branch ?? 'master' }}
    git pull
@endtask

@task('sentry-file', ['on' => 'local'])
    echo {{ $commit }} > .sentry-commit
    echo {{ $release }} > .sentry-release
    git add -f .sentry-commit .sentry-release
    git commit -m 'add sentry files'
@endtask

@task('sentry-release', ['on' => 'web'])
    php artisan sentry:release --force -vvv --release=$(cat .sentry-release) --environment=$SENTRY_ENV --commit=$(cat .sentry-commit)
@endtask

@task('sentry-local', ['on' => 'local'])
    test -x ~/.local/bin/sentry-cli || curl -sL https://sentry.io/get-cli/ | INSTALL_DIR=~/.local/bin bash
    source .env
    php artisan sentry:release --force -vvv --release={{ $release }} --environment={{ $environment ?? 'production' }} --commit={{ $commit }}
@endtask

@task('git-config', ['on' => 'local'])
    git remote remove deploy || true
    git remote add deploy {{ $site.'@'.$host.':'.$site.'.git' }}
@endtask
