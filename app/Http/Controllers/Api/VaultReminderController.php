<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\FlagState;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderResource;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VaultReminderController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $request->user()->account->vaults()
            ->addressedBy($vault)
            ->firstOrFail();

        $validated = $request->validate([
            'about' => ['nullable', 'string', Rule::in(['contact', 'group'])],
        ]);

        $about = match ($validated['about'] ?? null) {
            'contact' => [new Contact()->getMorphClass()],
            'group' => [new Group()->getMorphClass()],
            default => [new Contact()->getMorphClass(), new Group()->getMorphClass()],
        };

        if (! FlagState::isOn('groups', $request->user()->account)) {
            $about = array_values(array_diff($about, [new Group()->getMorphClass()]));
        }

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $reminders = Reminder::query()
            ->where('vault_id', $vault->id)
            ->whereIn('remindable_type', $about)
            ->with(['vault', 'field', 'fieldValue', 'remindable'])
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => ReminderResource::collection($reminders->items())->resolve(),
            'meta' => [
                'page' => $reminders->currentPage(),
                'per_page' => $reminders->perPage(),
                'total' => $reminders->total(),
            ],
        ]);
    }
}
