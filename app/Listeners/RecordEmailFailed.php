<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RecordEmailDeliveryOutcome;
use App\Enums\EmailDeliveryStatusEnum;
use Resend\Laravel\Events\EmailFailed;

/**
 * Note that the provider never managed to send one of our emails.
 *
 * It is recorded as a bounce. The two facts differ in whose fault they are,
 * but they agree on the one thing a reader of the journal needs: the email
 * reached nobody. Keeping them apart would mean a column and a word this
 * product has no use for yet.
 */
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
