<?php

namespace App\Http\Resources\Call;

use Illuminate\Http\Resources\Json\Resource;

class Call extends Resource
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
            'object' => 'call',
            'contact_id' => $this->contact_id,
            'called_at' => (string)$this->called_at,
            'content' => $this->content,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format('Y-m-d\TH:i:s\Z')),
        ];
    }
}
