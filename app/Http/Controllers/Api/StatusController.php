<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Say whether this instance is up, and which release it is running.
 *
 * Nothing here needs a token. It is meant for a monitor, and for the first call
 * somebody makes while wiring up a client, so it has to answer before there is
 * any credential to answer with.
 */
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
