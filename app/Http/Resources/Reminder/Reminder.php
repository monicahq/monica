<?php

namespace App\Http\Resources\Reminder;

use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class Reminder extends Resource
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
            'object' => 'reminder',
            'title' => $this->title,
            'description' => $this->description,
            'frequency_type' => $this->frequency_type,
            'frequency_number' => $this->frequency_number,
            'last_triggered_date' => (is_null($this->last_triggered) ? null : $this->last_triggered->format(config('api.timestamp_format'))),
            'next_expected_date' => (is_null($this->next_expected_date) ? null : $this->next_expected_date->format(config('api.timestamp_format'))),
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
