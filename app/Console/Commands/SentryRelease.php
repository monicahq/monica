<?php

namespace App\Console\Commands;

use function Safe\exec;
use function Safe\mkdir;
use Illuminate\Console\Command;
use function Safe\file_put_contents;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Output\OutputInterface;

class SentryRelease extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentry:release
                            {--force : Force the operation to run when in production.}
                            {--release= : release version for sentry.}
                            {--store-release : store release version in config/.release file.}
                            {--commit= : commit associated with this release.}
                            {--environment= : sentry environment.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a release for sentry';

    /**
     * Installation path of sentry cli.
     *
     * @var string
     */
    private $install_dir;

    /**
     * sentry cli name.
     *
     * @var string
     */
    private const SENTRY_CLI = 'sentry-cli';

    /**
     * Sentry cli download url.
     *
     * @var string
     */
    private const SENTRY_URL = 'https://sentry.io/get-cli/';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! config('monica.sentry_support') || ! $this->check()) {
            return;
        }

        if ($this->confirmToProceed()) {
            $this->install_dir = env('SENTRY_ROOT', getenv('HOME').'/.local/bin');

            $release = $this->option('release') ?? config('sentry.release');
            $commit = $this->option('commit') ??
                    (is_dir(__DIR__.'/../../../.git') ? trim(exec('git log --pretty="%H" -n1 HEAD')) : $release);

            // Sentry update
            $this->exec('Update sentry', $this->getSentryCli().' update');

            // Create a release
            $this->execSentryCli('Create a release', 'releases new '.$release.' --finalize --project '.config('sentry-release.project'));

            // Associate commits with the release
            $this->execSentryCli('Associate commits with the release', 'releases set-commits '.$release.' --commit "'.config('sentry-release.repo').'@'.$commit.'"');

            // Create a deploy
            $this->execSentryCli('Create a deploy', 'releases deploys '.$release.' new --env '.$this->option('environment').' --name '.config('monica.app_version'));

            if ($this->option('store-release')) {
                // Set sentry release
                $this->line('Store release in config/.release file', null, OutputInterface::VERBOSITY_VERBOSE);
                file_put_contents(__DIR__.'/../../../config/.release', $release);
            }
        }
    }

    private function check(): bool
    {
        $check = true;
        if (empty(config('sentry-release.auth_token'))) {
            $this->error('You must provide an auth_token (SENTRY_AUTH_TOKEN)');
            $check = false;
        }
        if (empty(config('sentry-release.organisation'))) {
            $this->error('You must provide an organisation slug (SENTRY_ORG)');
            $check = false;
        }
        if (empty(config('sentry-release.project'))) {
            $this->error('You must set the project (SENTRY_PROJECT)');
            $check = false;
        }
        if (empty(config('sentry-release.repo'))) {
            $this->error('You must set the repository (SENTRY_REPO)');
            $check = false;
        }
        if (empty($this->option('environment'))) {
            $this->error('No environment given');
            $check = false;
        }

        return $check;
    }

    private function getSentryCli()
    {
        if (! file_exists($this->install_dir.'/'.self::SENTRY_CLI)) {
            mkdir($this->install_dir, 0777, true);
            $this->exec('Downloading sentry-cli', 'curl -sL '.self::SENTRY_URL.' | INSTALL_DIR='.$this->install_dir.' bash');
        }

        return $this->install_dir.'/'.self::SENTRY_CLI;
    }

    private function execSentryCli($message, $command)
    {
        $this->exec($message, $this->getSentryCli().' '.$command);
    }
}
