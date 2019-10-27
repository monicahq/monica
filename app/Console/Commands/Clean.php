<?php

namespace App\Console\Commands;

use App\Models\User\SyncToken;
use Illuminate\Console\Command;
use App\Events\TokenDeleteEvent;
use App\Services\Instance\TokenClean;
use Illuminate\Support\Facades\Event;

class Clean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:clean
                            {--dry-run : Do everything except actually clean monica instance.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean monica instance';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        Event::listen(TokenDeleteEvent::class, function ($event) {
            $this->handleTokenDelete($event->token);
        });

        app(TokenClean::class)->execute([
            'dryrun' => (bool) $this->option('dry-run'),
        ]);
    }

    /**
     * Handle TokenDeleteEvent event.
     *
     * @param SyncToken $token
     */
    private function handleTokenDelete($token)
    {
        $this->info('Delete token '.$token->id.' - User '.$token->user_id.' - Type '.$token->name.' - timestamp '.$token->timestamp);
    }
}
