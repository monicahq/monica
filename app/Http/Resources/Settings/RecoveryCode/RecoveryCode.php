<?php

namespace App\Http\Resources\Settings\RecoveryCode;

use Illuminate\Http\Resources\Json\Resource;

class RecoveryCode extends Resource
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
            'object' => 'recoverycode',
            'recovery' => $this->recovery,
            'used' => (bool) $this->used,
        ];
    }
}
