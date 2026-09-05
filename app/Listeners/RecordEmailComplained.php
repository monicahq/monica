<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RecordEmailDeliveryOutcome;
use App\Enums\EmailDeliveryStatusEnum;
use Resend\Laravel\Events\EmailComplained;

class RecordEmailComplained
{
    public function handle(EmailComplained $event): void
    {
        new RecordEmailDeliveryOutcome(
            payload: $event->payload,
            outcome: EmailDeliveryStatusEnum::Complained,
        )->execute();
    }
}
