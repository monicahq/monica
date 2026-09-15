<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('monica:setup-telegram')]
#[Description('Tell Telegram where to send what people say to the bot, so a conversation can be connected to an account')]
class SetupTelegramCommand extends Command
{
    public function handle(): int
    {
        if (! config('services.telegram.installed')) {
            $this->components->error('The Telegram integration is not installed. Run: composer require laravel-notification-channels/telegram');

            return self::FAILURE;
        }

        foreach (['services.telegram.token' => 'TELEGRAM_BOT_TOKEN', 'services.telegram.webhook_secret' => 'TELEGRAM_BOT_WEBHOOK_SECRET'] as $key => $variable) {
            if (blank(config($key))) {
                $this->components->error("{$variable} is not set, so there is nothing to register. Generate a secret with: openssl rand -hex 32");

                return self::FAILURE;
            }
        }

        $url = route('telegram.webhook');

        $this->components->info("Registering {$url} with Telegram.");

        $response = Http::post('https://api.telegram.org/bot'.config('services.telegram.token').'/setWebhook', [
            'url' => $url,
            'secret_token' => config('services.telegram.webhook_secret'),
        ]);

        if ($response->failed()) {
            $this->components->error('Telegram refused it: '.$response->json('description', $response->body()));

            return self::FAILURE;
        }

        $this->components->info((string) $response->json('description', 'Done.'));

        return self::SUCCESS;
    }
}
