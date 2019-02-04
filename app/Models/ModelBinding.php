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
     * @return mixed|null
     */
    public function resolveRouteBinding($value)
    {
        if (Auth::guest()) {
            return;
        }

        return $this->where('account_id', Auth::user()->account_id)
            ->where($this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
