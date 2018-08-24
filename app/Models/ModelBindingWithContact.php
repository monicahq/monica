<?php

namespace App\Models;

use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;

abstract class ModelBindingWithContact extends Model
{
    public function resolveRouteBinding($value)
    {
        $contact = Route::current()->parameter('contact');

        return $this->where('account_id', auth()->user()->account_id)
            ->where('contact_id', $contact->id)
            ->where($this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
