@servers(['local' => 'localhost', 'web' => '{{ $site }}@deploy.{{ $region }}.frbit.com'])

@setup
    $commit = exec('git log --pretty="%H" -n1 HEAD');
    //$version = exec('php artisan monica:getversion');
    $release = exec('git log --pretty="%h" -n1 HEAD');
@endsetup

@story('deploy')
    sentry-set
    git
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

@task('sentry-set', ['on' => 'web'])
    echo {{ $release }} > .sentry-release
@endtask

@task('sentry', ['on' => 'web'])
    php artisan sentry:release --force -vvv --release={{ $release }} --environment=production --commit={{ $commit }}
    php artisan config:cache
@endtask
