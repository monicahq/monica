<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

abstract class ModelBinding extends Model
{
    /**
     * Resolve binding.
     *
     * @param  string  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (Auth::guest()) {
            return;
        }

        return $this->where('account_id', Auth::user()->account_id)
            ->where($this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
