<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\LifeEventTimeline;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LifeEventSliceController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $request->user()->account->vaults()
            ->addressedBy($vault)
            ->firstOrFail();

        $validated = $request->validate([
            'contact' => ['nullable', 'string'],
            'life_event_type' => ['nullable', 'string'],
        ]);

        $today = CarbonImmutable::now($request->user()->timezone)->startOfDay();

        $timeline = new LifeEventTimeline(
            vault: $vault,
            today: $today,
            type: isset($validated['life_event_type'])
                ? $request->user()->account->lifeEventTypes()->withTrashed()->where('uuid', $validated['life_event_type'])->firstOrFail()
                : null,
            contact: isset($validated['contact'])
                ? Contact::query()->where('vault_id', $vault->id)->addressedBy($validated['contact'])->firstOrFail()
                : null,
        );

        return response()->json([
            'data' => $timeline->slices(),
            'meta' => [
                'total' => $timeline->total(),
                'today' => $today->toDateString(),
            ],
        ]);
    }
}
