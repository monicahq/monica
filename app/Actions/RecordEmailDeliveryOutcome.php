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
 */
class RecordEmailDeliveryOutcome
{
    /** @param  array<string, mixed>  $payload */
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
            }
        }

        Log::warning('A Resend delivery report carried no instant this could read, so it is recorded as having happened now.', [
            'reported' => is_string($reported) ? $reported : gettype($reported),
        ]);

        return CarbonImmutable::now();
    }
}
