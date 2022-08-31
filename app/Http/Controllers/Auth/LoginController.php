<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\WallpaperHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Display the login view.
     *
     * @param  Request  $request
     * @return \Inertia\Response
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'wallpaperUrl' => WallpaperHelper::getRandomWallpaper(),
        ]);
    }
}
