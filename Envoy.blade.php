@servers(['local' => 'localhost', 'web' => '{{ $site }}@deploy.{{ $region }}.frbit.com'])

@setup
    $commit = exec('git log --pretty="%H" -n1 HEAD');
    //$version = exec('php artisan monica:getversion');
    $release = exec('git log --pretty="%h" -n1 HEAD');
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
    git fetch origin
    git checkout {{ $branch ?? 'master' }}
    git pull
    git push -u fortrabbit {{ $branch ?? 'master' }}
@endtask

@task('composer', ['on' => 'web'])
    composer install --no-interaction --no-dev
@endtask

@task('update', ['on' => 'web'])
    php artisan monica:update --force -vvv
@endtask

@task('sentry', ['on' => 'web'])
    php artisan sentry:release --force -vvv --release={{ $release }} --environment=production --commit={{ $commit }}
    php artisan config:cache
@endtask
