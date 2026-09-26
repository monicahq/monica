<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RegisterTelegramWebhook;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

#[Signature('monica:setup-telegram')]
#[Description('Tell Telegram where to send what people say to the bot, so a conversation can be connected to an account')]
class SetupTelegramCommand extends Command
{
    public function handle(): int
    {
        try {
            $registration = new RegisterTelegramWebhook(administrator: null)->execute();
        } catch (ValidationException $e) {
            $this->components->error((string) collect($e->errors())->flatten()->first());

            return self::FAILURE;
        }

        $this->components->info("Registered {$registration->url} with Telegram.");

        return self::SUCCESS;
    }
}
