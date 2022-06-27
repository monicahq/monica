<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MeiliSearch\Client;

class SetupApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup everything that is needed for Monica to work';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->line('');
        $this->line('-----------------------------');
        $this->line('|');
        $this->line('| Welcome to Monica');
        $this->line('|');
        $this->line('-----------------------------');

        $this->line('-> Creating indexes on Meilisearch. Make sure Meilisearch is running.');

        $client = new Client(config('scout.meilisearch.host'), config('scout.meilisearch.key'));
        $index = $client->index('contacts');
        $index->updateFilterableAttributes([
            'id',
            'vault_id',
        ]);
        $index = $client->index('notes');
        $index->updateFilterableAttributes([
            'id',
            'vault_id',
            'contact_id',
        ]);
        $index = $client->index('groups');
        $index->updateFilterableAttributes([
            'id',
            'vault_id',
        ]);

        $this->line('✓ Indexes created');
    }
}
