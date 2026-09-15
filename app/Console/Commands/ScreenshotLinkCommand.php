<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\App\Auth\ScreenshotLinkController;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

#[Signature('monica:screenshot-link {email : The address of the user to sign in as} {--to=/ : The path the link lands on}')]
#[Description('Mint a short lived signed link that signs somebody in, for taking a screenshot of a screen behind the sign in')]
class ScreenshotLinkCommand extends Command
{
    public function handle(): int
    {
        if (! ScreenshotLinkController::isAvailable()) {
            $this->components->error('Screenshot links are switched off. Set MONICA_SCREENSHOT_LINK_ENABLED=true, outside production, and try again.');

            return self::FAILURE;
        }

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! ScreenshotLinkController::isDevelopmentHost($host)) {
            $this->components->error("This instance answers on {$host}, which is not a development host, so a link would lead nowhere.");

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');

        if (! User::query()->where('email', $email)->exists()) {
            $this->components->error("No user of this instance goes by {$email}.");

            return self::FAILURE;
        }

        $minutes = (int) config('monica.screenshot_link.lifetime_in_minutes');

        $this->line(URL::temporarySignedRoute(
            'auth.screenshotLink.show',
            now()->addMinutes($minutes),
            ['email' => $email, 'to' => (string) $this->option('to')],
        ));

        $this->components->info("The link signs in as {$email} and stops working in {$minutes} minutes.");

        return self::SUCCESS;
    }
}
