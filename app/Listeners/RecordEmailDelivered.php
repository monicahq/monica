<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RecordEmailDeliveryOutcome;
use App\Enums\EmailDeliveryStatusEnum;
use Resend\Laravel\Events\EmailDelivered;

/**
 * Note that the provider delivered one of our emails.
 */
class RecordEmailDelivered
{
    public function handle(EmailDelivered $event): void
    {
        new RecordEmailDeliveryOutcome(
            payload: $event->payload,
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();
    }
}
