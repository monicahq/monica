// See Envoy doc: https://laravel.com/docs/5.8/envoy
// 
// Configure with: `envoy run git-config --host=my-host --site=site-conf`
// Deploy with: `envoy run deploy`

@servers(['local' => 'localhost', 'web' => '{{ $site }}@{{ $host }}'])

@task('deploy', ['on' => 'local'])
    git fetch origin
    git checkout {{ $branch ?? 'master' }}
    git pull
    git push -u deploy {{ $branch ?? 'master' }}
@endtask

@task('git-config', ['on' => 'local'])
    git remote remove deploy || true
    git remote add deploy {{ $site.'@'.$host.':'.$site.'.git' }}
@endtask
