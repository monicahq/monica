<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;

class NotFoundController extends Controller
{
    public function show(): never
    {
        abort(404);
    }
}
