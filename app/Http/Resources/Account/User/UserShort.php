<?php

namespace App\Http\Resources\Account\User;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\Settings\Currency\Currency as CurrencyResource;

class UserShort extends Resource
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
            'object' => 'user',
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'is_admin' => (bool) $this->admin,
            'account' => new Account($this->account),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
