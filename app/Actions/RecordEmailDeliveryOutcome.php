<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailDeliveryStatusEnum;
use App\Models\EmailSent;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Record what the provider says became of an email we sent.
 *
 * One report, one timestamp. Which timestamp is the outcome the listener names,
 * and the row is the one carrying the identifier the provider gave the email
 * when it left.
 *
 * The payload arrives whole rather than parsed because four listeners reach
 * here, and parsing in each of them would state four times over which key holds
 * the instant. That is exactly the detail worth stating once: the top level
 * `created_at` is when the reported fact happened, while `data.created_at` is
 * when the email was composed, and reading the second as the first is wrong by
 * however long the email sat in a queue.
 *
 * Nothing is recorded at all where no signing secret is configured. The package
 * attaches its signature check only when there is one, so without it the
 * endpoint accepts any request that reaches it, and the refusal has to happen
 * here instead. That is the whole of it: the endpoint's answer is the package's
 * to choose, and this decides what it is allowed to change.
 *
 * Nothing in here throws. The endpoint answers 200, an exception would turn
 * that into a 500, and the provider retries a 500 until it expires. So every
 * report this cannot use is dropped and written to the log instead, while one it
 * can use is not logged at all, which is what keeps the log a signal rather than
 * a stream.
 *
 * The row is looked up across every account, which is the one place in the
 * application that does so. There is no user here to scope to: the report comes
 * from outside, about an email the product itself sent, and the only thing that
 * names the row is an identifier the provider chose. Nothing in the payload is
 * trusted beyond that identifier, and no account is named by it.
 */
class RecordEmailDeliveryOutcome
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $payload,
        private readonly EmailDeliveryStatusEnum $outcome,
    ) {}

    public function execute(): ?EmailSent
    {
        if (! $this->cameFromTheProvider()) {
            Log::warning('Ignored a Resend delivery report, because no webhook secret is configured and it therefore cannot be proven to come from Resend.');

            return null;
        }

        $column = $this->column();

        if ($column === null) {
            return null;
        }

        $uuid = Arr::get($this->payload, 'data.email_id');

        if (! is_string($uuid) || $uuid === '') {
            Log::warning('Ignored a Resend delivery report that named no email.', [
                'type' => Arr::get($this->payload, 'type'),
            ]);

            return null;
        }

        $emailSent = EmailSent::query()
            ->where('uuid', $uuid)
            ->first();

        if ($emailSent === null) {
            Log::warning('Ignored a Resend delivery report about an email this journal does not hold.', [
                'email_id' => $uuid,
            ]);

            return null;
        }

        // An instant already recorded is left alone, which is what makes a
        // repeated report change nothing and makes the order they arrive in
        // stop mattering. Which of them the row then reads as is the model's
        // decision, not ours.
        if ($emailSent->getAttribute($column) !== null) {
            return $emailSent;
        }

        $emailSent->update([$column => $this->happenedAt()]);

        return $emailSent;
    }

    private function cameFromTheProvider(): bool
    {
        $secret = config('resend.webhook.secret');

        return is_string($secret) && $secret !== '';
    }

    private function column(): ?string
    {
        return match ($this->outcome) {
            EmailDeliveryStatusEnum::Delivered => 'delivered_at',
            EmailDeliveryStatusEnum::Bounced => 'bounced_at',
            EmailDeliveryStatusEnum::Complained => 'complained_at',
            // No report announces that an email was sent. We wrote that
            // ourselves as it left, so there is no column to reach for here.
            EmailDeliveryStatusEnum::Sent => null,
        };
    }

    private function happenedAt(): CarbonImmutable
    {
        $reported = Arr::get($this->payload, 'created_at');

        if (is_string($reported)) {
            try {
                return CarbonImmutable::parse($reported);
            } catch (InvalidFormatException) {
                // Fall through to now. The fact is worth keeping even when the
                // moment it carries is unreadable, and the log says so.
            }
        }

        Log::warning('A Resend delivery report carried no instant this could read, so it is recorded as having happened now.', [
            'reported' => is_string($reported) ? $reported : gettype($reported),
        ]);

        return CarbonImmutable::now();
    }
}
