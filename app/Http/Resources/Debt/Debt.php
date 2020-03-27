<?php

namespace App\Http\Resources\Debt;

use App\Helpers\DateHelper;
use App\Helpers\MoneyHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

/**
 * @extends JsonResource<\App\Models\Contact\Debt>
 */
class Debt extends JsonResource
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
            'object' => 'debt',
            'in_debt' => $this->in_debt,
            'status' => $this->status,
            'amount' => $this->amount,
            'amount_with_currency' => MoneyHelper::format((int) $this->amount),
            'reason' => $this->reason,
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
