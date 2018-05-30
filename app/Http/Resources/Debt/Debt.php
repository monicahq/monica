<?php

namespace App\Http\Resources\Debt;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class Debt extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'debt',
            'in_debt' => $this->in_debt,
            'status' => $this->status,
            'amount' => $this->amount,
            'amount_with_currency' => MoneyHelper::format($this->amount),
            'reason' => $this->reason,
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
