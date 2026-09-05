<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        ], 200);
    }
}
