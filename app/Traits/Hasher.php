<?php

namespace App\Traits;

use App\Helpers\IdHasher;
use Illuminate\Support\Facades\Route;

/**
 * @see Illuminate\Contracts\Routing\UrlRoutable
 */
trait Hasher
{
    public function getRouteKey()
    {
        return app('idhasher')->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value)
    {
        $id = app('idhasher')->decodeId($value);

        $filter = $this->where('account_id', auth()->user()->account_id);
        $contact = Route::current()->parameter('contact');
        if (! is_null($contact)) {
            $filter = $filter->where('contact_id', $contact->id);
        }

        return $filter->findOrFail($id);
    }

    public function hashID()
    {
        return app('idhasher')->encodeId($this->id);
    }
}
