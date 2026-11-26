<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatusController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            return response()->json(['status' => 'error'], 503);
        }

        return response()->json([
            'status' => 'ok',
            'version' => config('monica.version'),
            'instance' => config('monica.instance'),
            'mobile' => [
                'api_version' => 1,
                'client_id' => MobileSession::CLIENT_ID,
                'scope' => MobileSession::SCOPE,
                'registration_enabled' => (bool) config('monica.registration_enabled'),
                'minimum_android_version' => config('monica.mobile.minimum_android_version') ?: null,
            ],
        ], 200);
    }
}
