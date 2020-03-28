<?php

namespace App\Http\Resources\Contact;

class ContactWithContactFields extends Contact
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->toArrayInternal($request, true);
    }
}
