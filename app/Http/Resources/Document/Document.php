<?php

namespace App\Http\Resources\Document;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class Document extends Resource
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
            'object' => 'document',
            'original_filename' => $this->original_filename,
            'new_filename' => $this->new_filename,
            'filesize' => $this->filesize,
            'type' => $this->type,
            'mime_type' => $this->mime_type,
            'number_of_downloads' => $this->number_of_downloads,
            'link' => $this->getDownloadLink(),
            'account' => [
                'id' => $this->account->id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
