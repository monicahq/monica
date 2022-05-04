<?php

namespace App\Http\Resources\Gift;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Photo\Photo as PhotoResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

/**
 * @extends JsonResource<\App\Models\Contact\Gift>
 */
class Gift extends JsonResource
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
            'object' => 'gift',
            'name' => $this->name,
            'comment' => $this->comment,
            'url' => $this->url,
            'amount' => $this->amount,
            'value' => $this->value,
            'amount_with_currency' => $this->displayValue,
            'status' => $this->status,
            'date' => DateHelper::getDate($this->date),
            'recipient' => new ContactShortResource($this->recipient),
            'photos' => PhotoResource::collection($this->photos),
            'contact' => new ContactShortResource($this->contact),
            'account' => [
                'id' => $this->account_id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
