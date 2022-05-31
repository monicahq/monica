<?php

namespace App\Http\Resources\Account\User;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\Settings\Currency\Currency as CurrencyResource;

/**
 * @extends JsonResource<\App\Models\User\User>
 */
class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'object' => 'user',
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'me_contact' => new ContactShortResource($this->me),
            'timezone' => $this->timezone,
            'currency' => new CurrencyResource($this->currency),
            'locale' => $this->locale,
            'is_policy_compliant' => $this->policy_compliant,
            'account' => [
                'id' => $this->account_id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
