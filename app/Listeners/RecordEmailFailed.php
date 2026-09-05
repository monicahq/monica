<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RecordEmailDeliveryOutcome;
use App\Enums\EmailDeliveryStatusEnum;
use Resend\Laravel\Events\EmailFailed;

class RecordEmailFailed
{
    public function handle(EmailFailed $event): void
    {
        new RecordEmailDeliveryOutcome(
            payload: $event->payload,
            outcome: EmailDeliveryStatusEnum::Bounced,
        )->execute();
    }
}
