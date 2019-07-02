<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\TokenDeleteEvent;
use App\Services\Instance\TokenClean;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\ConfirmableTrait;

class Clean extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:clean
                            {--force : Force the operation to run when in production.}
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
        $tokens = collect();
        Event::listen(TokenDeleteEvent::class, function($event) use ($tokens) {
            $tokens->push($event->token);
        });

        if ($this->confirmToProceed()) {
            app(TokenClean::class)->execute([
                'dryrun' => (bool) $this->option('dry-run') ?: false,
            ]);
        }

        $this->displayTokens($tokens);
    }

    /**
     * @param \Illuminate\Support\Collection $tokens
     */
    private function displayTokens($tokens)
    {
        foreach ($tokens as $token) {
            $this->info('Token '.$token->id.' deleted - User '.$token->user_id.' - timestamp '.$token->timestamp);
        }
    }
}
