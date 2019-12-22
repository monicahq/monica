<?php

namespace App\Http\Resources\Gift;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class Gift extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'gift',
            'name' => $this->name,
            'comment' => $this->comment,
            'url' => $this->url,
            'amount' => $this->value,
            'amount_with_currency' => $this->amount,
            'status' => $this->status,
            'date' => $this->date ? DateHelper::getShortDate($this->date) : '',
            'recipient' => new ContactShortResource($this->recipient),
            'contact' => new ContactShortResource($this->contact),
            'account' => [
                'id' => $this->account_id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
