<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A vault, as the API hands it out.
 *
 * The address is the uuid and never the primary key. A vault is a compartment,
 * and handing out a sequential number would say how many an instance holds and
 * let one address be guessed from the one beside it.
 *
 * Three things are deliberately absent. The account, because it is always the
 * caller's. When the vault was last opened, because that is a person clicking in
 * a browser and says nothing to a program. And the structure a new contact in it
 * starts from, because a vault holds one of those per kind of record, so a single
 * field here would answer a narrower question than its name suggests. Reading
 * those is an endpoint of its own.
 *
 * @mixin Vault
 */
class VaultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'vault',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'colour' => $this->colour,
                'position' => $this->position,
                'is_default' => $this->is_default,
                'contact_count' => $this->contact_count,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.vaults.show', $this->uuid),
            ],
        ];
    }
}
