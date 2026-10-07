<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\ViewModels\NotificationBarViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.notifications._list', [
            'viewModel' => new NotificationBarViewModel(user: $request->user()),
        ]);
    }
}
