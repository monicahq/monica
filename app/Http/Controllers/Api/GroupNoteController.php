<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateGroupNote;
use App\Actions\DestroyGroupNote;
use App\Actions\UpdateGroupNote;
use App\Exceptions\NoteIsEmpty;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupNoteResource;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class GroupNoteController extends Controller
{
    public function index(Request $request, string $vault, string $group): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $notes = GroupNote::query()
            ->where('group_id', $group->id)
            ->with(GroupNoteResource::RELATIONS)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => array_map(
                fn (GroupNote $note): array => new GroupNoteResource($note)->resolve($request),
                $notes->items(),
            ),
            'meta' => [
                'page' => $notes->currentPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
        ]);
    }

    public function show(Request $request, string $vault, string $group, string $note): JsonResponse
    {
        $note = $this->note($this->group($this->vault($request, $vault), $group), $note);

        return new GroupNoteResource($note)->response()->setStatusCode(200);
    }

    public function create(Request $request, string $vault, string $group): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);

        $validated = $request->validate(['body' => ['required', 'string', 'max:65535']]);

        try {
            $note = new CreateGroupNote(user: $request->user(), vault: $vault, group: $group, body: $validated['body'])->execute();
        } catch (NoteIsEmpty $e) {
            throw ValidationException::withMessages(['body' => $e->getMessage()]);
        }

        return new GroupNoteResource($note->load(GroupNoteResource::RELATIONS))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $group, string $note): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);
        $note = $this->note($group, $note);

        $validated = $request->validate(['body' => ['sometimes', 'string', 'max:65535']]);

        try {
            new UpdateGroupNote(user: $request->user(), vault: $vault, group: $group, note: $note, body: $validated['body'] ?? $note->body)->execute();
        } catch (NoteIsEmpty $e) {
            throw ValidationException::withMessages(['body' => $e->getMessage()]);
        }

        return new GroupNoteResource($note->refresh()->load(GroupNoteResource::RELATIONS))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $vault, string $group, string $note): Response
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);

        new DestroyGroupNote(user: $request->user(), vault: $vault, group: $group, note: $this->note($group, $note))->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function group(Vault $vault, string $address): Group
    {
        return Group::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->firstOrFail();
    }

    private function note(Group $group, string $address): GroupNote
    {
        return GroupNote::query()
            ->where('group_id', $group->id)
            ->where('uuid', $address)
            ->with(GroupNoteResource::RELATIONS)
            ->firstOrFail();
    }
}
