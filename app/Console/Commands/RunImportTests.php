<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'monica:test-imports')]
class RunImportTests extends Command
{
    protected $signature = 'monica:test-imports
                            {--database=monica_test : MySQL test database name}
                            {--username=root : MySQL username}
                            {--password= : MySQL password}
                            {--host=127.0.0.1 : MySQL host}
                            {--port=3306 : MySQL port}';

    protected $description = 'Run all import system tests with MySQL database';

    public function handle(): int
    {
        $testFiles = [
            'tests/Unit/Domains/Contact/ManageContact/Services/CsvToVCardTest.php',
            'tests/Unit/Domains/Contact/ManageContact/Jobs/ProcessImportBatchTest.php',
            'tests/Feature/Controllers/ContactImportControllerTest.php',
            'tests/Unit/Domains/Contact/ManageContact/Services/ImportFileTest.php',
        ];

        $env = [
            'DB_TEST_DRIVER' => 'mysql',
            'DB_TEST_DATABASE' => $this->option('database'),
            'DB_TEST_USERNAME' => $this->option('username'),
            'DB_TEST_PASSWORD' => $this->option('password') ?? '',
            'DB_TEST_HOST' => $this->option('host'),
            'DB_TEST_PORT' => $this->option('port'),
            'APP_ENV' => 'testing',
            'APP_KEY' => 'base64:NTrXToqFZJlv48dgPc+kNpc3SBt333TfDnF1mDShsBg=',
        ];

        $fileArgs = implode(' ', array_map('escapeshellarg', $testFiles));
        $envString = collect($env)
            ->map(fn ($v, $k) => $k.'='.escapeshellarg($v))
            ->implode(' ');

        $this->info('Running import system tests...');

        $total = count($testFiles);
        $overallExitCode = 0;

        foreach ($testFiles as $i => $file) {
            $label = '['.($i + 1).'/'.$total.'] '.basename($file);
            $this->line('');
            $this->info('━━━ '.$label.' ━━━');

            $command = $envString.' php vendor/bin/phpunit --colors=always --testdox '.escapeshellarg($file);

            passthru($command, $exitCode);

            if ($exitCode !== 0) {
                $overallExitCode = $exitCode;
            }
        }

        $this->line('');
        if ($overallExitCode === 0) {
            $this->info('All import tests passed.');
        } else {
            $this->error('Some import tests failed.');
        }

        return $exitCode;
    }
}
