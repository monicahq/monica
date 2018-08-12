<?php

namespace App\Traits;

use Illuminate\Support\Facades\Route;

trait Binding
{
    public function resolveRouteBinding($value)
    {
        $filter = $this->where('account_id', auth()->user()->account_id);

        $contact = Route::current()->parameter('contact');
        if (! is_null($contact)) {
            $filter = $filter->where('contact_id', $contact->id);
        }

        return $filter->findOrFail($value);
    }
}
