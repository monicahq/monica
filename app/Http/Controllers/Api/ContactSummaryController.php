<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactSummaryController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $request->user()->account->vaults()
            ->addressedBy($vault)
            ->firstOrFail();

        $validated = $request->validate([
            'filter' => ['nullable', 'string', Rule::in(['partial', 'full'])],
            'archived' => ['nullable', 'string', Rule::in(['excluded', 'included'])],
        ]);

        $filter = $validated['filter'] ?? null;

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_contact_summaries_per_page')),
            (int) config('monica.api_maximum_contact_summaries_per_page'),
        ));

        $contacts = Contact::query()
            ->where('vault_id', $vault->id)
            ->when($filter !== null, fn (Builder $query): Builder => $query->where('is_partial', $filter === 'partial'))
            ->when(($validated['archived'] ?? 'excluded') === 'excluded', fn (Builder $query): Builder => $query->where('is_archived', false))
            ->sortedBy(ContactSortEnum::Name, SortDirectionEnum::Ascending)
            ->paginate($perPage);

        return response()->json([
            'data' => array_map(fn (Contact $contact): array => [
                'id' => $contact->uuid,
                'name' => $contact->name,
                'is_partial' => $contact->is_partial,
                'is_archived' => $contact->is_archived,
            ], $contacts->items()),
            'meta' => [
                'page' => $contacts->currentPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }
}
