<?php

namespace App\Http\Resources\Company;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class Company extends Resource
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
            'object' => 'company',
            'name' => $this->name,
            'website' => $this->website,
            'number_of_employees' => $this->number_of_employees,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
