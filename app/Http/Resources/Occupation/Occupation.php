<?php

namespace App\Http\Resources\Occupation;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Company\Company as CompanyResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

/**
 * @extends JsonResource<\App\Models\Contact\Occupation>
 */
class Occupation extends JsonResource
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
            'object' => 'occupation',
            'title' => $this->title,
            'description' => $this->description,
            'salary' => $this->salary,
            'salary_unit' => $this->salary_unit,
            'currently_works_here' => (bool) $this->currently_works_here,
            'start_date' => DateHelper::getDate($this->start_date),
            'end_date' => DateHelper::getDate($this->end_date),
            'company' => new CompanyResource($this->company),
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
