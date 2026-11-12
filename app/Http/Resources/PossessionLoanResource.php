<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PossessionLoan;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionLoan */
class PossessionLoanResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['possession.vault', 'contact', 'conditionOut', 'conditionBack']);
        $today = CarbonImmutable::parse(CarbonImmutable::now($request->user()?->timezone)->toDateString());
        $contactName = $this->contactName();

        return [
            'type' => 'possession_loan',
            'id' => $this->uuid,
            'attributes' => [
                'direction' => $this->direction->value,
                'status' => $this->status->value,
                'is_overdue' => $this->isOverdueOn($today),
                'contact_id' => $contactName === null ? null : $this->contact?->uuid,
                'contact_name' => $contactName,
                'started_on' => $this->started_on->toDateString(),
                'due_on' => $this->due_on?->toDateString(),
                'returned_on' => $this->returned_on?->toDateString(),
                'condition_out_id' => $this->conditionOut?->uuid,
                'condition_back_id' => $this->conditionBack?->uuid,
                'deposit_amount' => $this->deposit_amount,
                'deposit_currency' => $this->deposit_currency?->value,
                'purpose' => $this->purpose,
                'is_in_provenance' => $this->is_in_provenance,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessions.loans.show', [$this->possession->vault->slug, $this->possession->uuid, $this->uuid]),
                'web' => route('possessions.loans.index', [$this->possession->vault, $this->possession]),
            ],
        ];
    }
}
