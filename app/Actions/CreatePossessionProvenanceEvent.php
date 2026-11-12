<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionProvenanceEventAttributes;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Record one moment in the provenance of a possession: its origin, a change of owner or of custody, or
 * what authenticated it. The date is written in the shape its precision gives, and a moment that cost
 * something points at the transaction recording it rather than holding an amount.
 */
class CreatePossessionProvenanceEvent
{
    private PossessionProvenanceEvent $event;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly PossessionProvenanceEventTypeEnum $type,
        private readonly PossessionProvenanceDatePrecisionEnum $datePrecision = PossessionProvenanceDatePrecisionEnum::Day,
        private readonly ?string $occurredOn = null,
        private readonly ?string $title = null,
        private readonly ?string $from = null,
        private readonly ?string $to = null,
        private readonly ?string $location = null,
        private readonly ?string $description = null,
        private readonly ?string $referenceNumber = null,
        private readonly ?string $sourceUrl = null,
        private readonly ?PossessionTransaction $transaction = null,
        private readonly bool $isVerified = false,
    ) {}

    public function execute(): PossessionProvenanceEvent
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->event;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }
    }

    private function create(): void
    {
        $this->event = PossessionProvenanceEvent::query()->create([
            'account_id' => $this->possession->account_id,
            'vault_id' => $this->possession->vault_id,
            'possession_id' => $this->possession->id,
            ...PossessionProvenanceEventAttributes::from(
                possession: $this->possession,
                type: $this->type,
                datePrecision: $this->datePrecision,
                occurredOn: $this->occurredOn,
                title: $this->title,
                from: $this->from,
                to: $this->to,
                location: $this->location,
                description: $this->description,
                referenceNumber: $this->referenceNumber,
                sourceUrl: $this->sourceUrl,
                transaction: $this->transaction,
                isVerified: $this->isVerified,
            ),
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionProvenanceEventCreation,
            parameters: ['name' => $this->possession->name],
            loggable: $this->event,
        )->onQueue('low');
    }
}
