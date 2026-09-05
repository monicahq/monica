<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RecordEmailDeliveryOutcome;
use App\Enums\EmailDeliveryStatusEnum;
use Resend\Laravel\Events\EmailBounced;

class RecordEmailBounced
{
    public function handle(EmailBounced $event): void
    {
        new RecordEmailDeliveryOutcome(
            payload: $event->payload,
            outcome: EmailDeliveryStatusEnum::Bounced,
        )->execute();
    }
}
