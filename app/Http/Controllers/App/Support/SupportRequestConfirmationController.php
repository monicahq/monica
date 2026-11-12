<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\ViewModels\Support\SupportConfirmationViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportRequestConfirmationController extends Controller
{
    public function show(Request $request, SupportRequest $supportRequest): View
    {
        return view('app.support.confirmation', [
            'viewModel' => new SupportConfirmationViewModel(
                user: $request->user(),
                supportRequest: $supportRequest,
            ),
        ]);
    }
}
