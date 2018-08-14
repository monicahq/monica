<?php

namespace App\Models;

use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;

abstract class ModelBindingHasherWithContact extends Model
{
    public function getRouteKey()
    {
        return app('idhasher')->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value)
    {
        $id = app('idhasher')->decodeId($value);

        $contact = Route::current()->parameter('contact');

        return $this->where('account_id', auth()->user()->account_id)
            ->where('contact_id', $contact->id)
            ->findOrFail($id);
    }

    public function hashID()
    {
        return $this->getRouteKey();
    }
}
