<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionProvenanceEventAttributes;
use App\Jobs\LogUserAction;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change what a provenance event of a possession says, under the rules that recorded it. The event
 * takes its new place in the chain from its new date.
 */
class UpdatePossessionProvenanceEvent
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionProvenanceEvent $event,
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
        $this->update();
        $this->log();

        return $this->event;
    }

    private function validate(): void
    {
        if ($this->event->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This provenance event is in another account.');
        }
    }

    private function update(): void
    {
        $this->event->update(PossessionProvenanceEventAttributes::from(
            possession: $this->event->possession,
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
        ));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionProvenanceEventUpdate,
            parameters: ['name' => $this->event->possession->name],
            loggable: $this->event,
        )->onQueue('low');
    }
}
