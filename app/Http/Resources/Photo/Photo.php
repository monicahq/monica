<?php

namespace App\Http\Resources\Photo;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class Photo extends Resource
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
            'object' => 'photo',
            'original_filename' => $this->original_filename,
            'new_filename' => $this->new_filename,
            'filesize' => $this->filesize,
            'mime_type' => $this->mime_type,
            'link' => $this->url(),
            'account' => [
                'id' => $this->account->id,
            ],
            'contact' => ContactShortResource::collection($this->contacts)[0],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
