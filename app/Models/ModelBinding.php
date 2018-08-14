<?php

namespace App\Models;

use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;

abstract class ModelBinding extends Model
{
    public function resolveRouteBinding($value)
    {
        return $this->where('account_id', auth()->user()->account_id)
            ->findOrFail($value);
    }
}
