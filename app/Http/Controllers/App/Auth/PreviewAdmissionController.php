<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Http\Controllers\Controller;
use App\Models\PreviewListEntry;
use App\ViewModels\Auth\RegisterViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewAdmissionController extends Controller
{
    public function show(Request $request): View
    {
        $token = (string) $request->route()->parameter('token');
        $entry = PreviewListEntry::findByAdmissionToken($token);

        abort_if($entry === null, 404);

        return view('app.auth.register', [
            'viewModel' => new RegisterViewModel(
                previewListEntry: $entry,
                admission: $token,
            ),
        ]);
    }
}
