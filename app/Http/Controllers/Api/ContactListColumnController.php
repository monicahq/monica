<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateContactListColumns;
use App\Enums\ContactListColumnSourceEnum;
use App\Helpers\ContactListCatalogue;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactListColumnResource;
use App\Models\ContactListColumn;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ContactListColumnController extends Controller
{
    public function show(Request $request, string $vault): JsonResponse
    {
        return $this->columnsOf($request, $this->vault($request, $vault));
    }

    public function update(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate([
            'columns' => ['sometimes', 'array'],
            'columns.*.source' => ['required', 'string', Rule::enum(ContactListColumnSourceEnum::class)],
            'columns.*.section' => ['nullable', 'required_if:columns.*.source,field', 'uuid'],
            'columns.*.field_key' => ['nullable', 'required_if:columns.*.source,field', 'string', 'max:64'],
            'columns.*.heading' => ['nullable', 'string', 'max:255'],
            'columns.*.is_visible' => ['required', 'boolean'],
        ]);

        if (! array_key_exists('columns', $validated)) {
            return $this->columnsOf($request, $vault);
        }

        try {
            new UpdateContactListColumns(
                user: $request->user(),
                vault: $vault,
                columns: array_map(fn (array $column): array => [
                    'source' => $column['source'],
                    'section' => $column['section'] ?? null,
                    'field_key' => $column['field_key'] ?? null,
                    'heading' => $column['heading'] ?? null,
                    'is_visible' => (bool) $column['is_visible'],
                ], array_values($validated['columns'])),
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['columns' => $e->getMessage()]);
        }

        return $this->columnsOf($request, $vault);
    }

    private function columnsOf(Request $request, Vault $vault): JsonResponse
    {
        $catalogue = new ContactListCatalogue($request->user()->account);

        return response()->json([
            'data' => array_map(
                fn (ContactListColumn $column): array => new ContactListColumnResource($column, $catalogue)->resolve($request),
                $catalogue->columnsOf($vault),
            ),
            'meta' => ['is_default' => ! $vault->contactListColumns()->exists()],
        ]);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }
}
