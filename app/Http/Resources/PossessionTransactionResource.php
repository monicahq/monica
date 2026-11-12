<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\File;
use App\Models\PossessionTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionTransaction */
class PossessionTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['possession.vault', 'documents']);

        return [
            'type' => 'possession_transaction',
            'id' => $this->uuid,
            'attributes' => [
                'type' => $this->type->value,
                'changes_ownership' => $this->type->changesOwnership(),
                'is_acquisition' => $this->possession->acquisitionTransaction()?->is($this->resource) ?? false,
                'occurred_on' => $this->occurred_on?->format('Y-m-d'),
                'counterparty' => $this->counterparty,
                'amount' => $this->amount,
                'tax' => $this->tax,
                'fees' => $this->fees,
                'shipping' => $this->shipping,
                'entered_total' => $this->entered_total,
                'total' => $this->total(),
                'total_source' => $this->totalSource(),
                'currency' => $this->currency?->value,
                'reference' => $this->reference,
                'source_url' => $this->source_url,
                'note' => $this->note,
                'documents' => $this->documents->map(fn (File $file): array => [
                    'id' => $file->uuid,
                    'name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size_in_bytes' => $file->size_in_bytes,
                ])->values()->all(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessions.transactions.show', [$this->possession->vault->slug, $this->possession->uuid, $this->uuid]),
                'web' => route('possessions.transactions.index', [$this->possession->vault, $this->possession]),
            ],
        ];
    }
}
