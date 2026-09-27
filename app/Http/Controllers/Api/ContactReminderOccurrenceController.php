<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\ContactReminderDates;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderOccurrenceResource;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactReminderOccurrenceController extends Controller
{
    public function index(Request $request, string $vault, string $contact): JsonResponse
    {
        $contact = $this->contact($this->vault($request, $vault), $contact);

        $validated = $request->validate([
            'category' => ['nullable', 'string', Rule::in(ContactReminderDates::categories())],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $paginated = ContactReminderDates::page(
            user: $request->user(),
            contact: $contact,
            category: $validated['category'] ?? null,
            perPage: $perPage,
            page: max(1, (int) $request->query('page', '1')),
        );

        return response()->json([
            'data' => ReminderOccurrenceResource::collection($paginated->items())->resolve(),
            'meta' => [
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function contact(Vault $vault, string $address): Contact
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->addressedBy($address)
            ->firstOrFail();
    }
}
