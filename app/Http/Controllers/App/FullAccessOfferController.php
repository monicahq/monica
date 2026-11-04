<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\MarkFullAccessOfferAsDismissed;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FullAccessOfferController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        new MarkFullAccessOfferAsDismissed(
            user: $request->user(),
        )->execute();

        return redirect()->back();
    }
}
