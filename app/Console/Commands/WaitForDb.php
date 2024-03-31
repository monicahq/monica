<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Symfony\Component\Console\Attribute\AsCommand;

use function Safe\chgrp;
use function Safe\chown;
use function Safe\touch;

#[AsCommand(name: 'waitfordb')]
class WaitForDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitfordb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wait for the database to be ready.';

    /**
     * Execute the console command.
     */
    public function handle(ConnectionFactory $factory): int
    {
        $maxAttempts = 30;
        $config = config('database.connections.'.config('database.default'));

        do {
            try {
                $connector = $factory->createConnector($config);
                $connector->connect($config);

                $this->info('Database ready.');

                return Command::SUCCESS;
            } catch (SQLiteDatabaseDoesNotExistException $e) {
                touch($e->path);
                chown($e->path, 'www-data');
                chgrp($e->path, 'www-data');
            } catch (\Exception $e) {
                $this->warn('Waiting for database to be ready…');
                sleep(1);
            } finally {
                $maxAttempts--;
            }
        } while ($maxAttempts > 0);

        $this->error('Unable to contact your database.');

        return Command::FAILURE;
    }
}
