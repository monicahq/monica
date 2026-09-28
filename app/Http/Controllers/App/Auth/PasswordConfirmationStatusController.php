<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordConfirmationStatusController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $secondsSinceConfirmation = now()->unix() - (int) $request->session()->get('auth.password_confirmed_at', 0);

        return response()->json([
            'confirmed' => $secondsSinceConfirmation < (int) config('auth.password_timeout'),
        ]);
    }
}
