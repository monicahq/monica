<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;

abstract class ModelBindingWithContact extends Model
{
    /**
     * Resolve binding with contact relation.
     *
     * @param  string  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $contact = Route::current()->parameter('contact');

        if (Auth::guest() || is_null($contact)) {
            return null;
        }

        return $this->where('account_id', Auth::user()->account_id)
            ->where('contact_id', $contact->id)
            ->where($this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
