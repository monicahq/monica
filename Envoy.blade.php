@servers(['local' => 'localhost', 'web' => '{{ $site }}@deploy.{{ $region }}.frbit.com'])

@setup
    $commit = exec('git log --pretty="%h" -n1 HEAD');
    $version = exec('php artisan monica:getversion');
@endsetup

@story('deploy')
    git
    update
    sentry
@endstory

@task('git-config', ['on' => 'local'])
    git remote add fortrabbit {{ $site }}@deploy.{{ $region }}.frbit.com:{{ $site }}.git
@endtask

@task('git', ['on' => 'local'])
    git push -u fortrabbit {{ $branch ?: 'master' }}
@endtask

@task('composer', ['on' => 'web'])
    composer install --no-interaction --no-dev
@endtask

@task('update', ['on' => 'web'])
    php artisan monica:update --force -vvv
@endtask

@task('sentry', ['on' => 'web'])
    
    source .env 2>/dev/null
    if [ "$SENTRY_SUPPORT" == "true" ]; then
  
        if [ ! -x ~/.local/bin/sentry-cli ]; then
            mkdir -p ~/.local/bin
            curl -sL https://sentry.io/get-cli/ | INSTALL_DIR=~/.local/bin bash
        fi

        if [ -x ~/.local/bin/sentry-cli ]; then
            export SENTRY_AUTH_TOKEN
            export SENTRY_ORG

            # Create a release
            ~/.local/bin/sentry-cli releases new -p $SENTRY_PROJECT --finalize {{ $commit }}

            # Associate commits with the release
            ~/.local/bin/sentry-cli releases set-commits --auto {{ $commit }}

            # Create a deploy
            ~/.local/bin/sentry-cli releases deploys {{ $commit }} new -e $SENTRY_ENV -n {{ $version }}
        fi
    fi

@endtask
