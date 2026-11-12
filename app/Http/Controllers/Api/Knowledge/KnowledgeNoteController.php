<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Knowledge;

use App\Actions\CreateKnowledgeNote;
use App\Actions\DestroyKnowledgeNote;
use App\Actions\UpdateKnowledgeNote;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeNoteResource;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KnowledgeNoteController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $record = $this->vault($request, $vault);

        $request->validate([
            'folder' => ['nullable', 'uuid'],
        ]);

        $notes = KnowledgeNote::query()
            ->where('vault_id', $record->id)
            ->when($request->filled('folder'), fn ($query) => $query->where('knowledge_folder_id', $this->folder($record, $request->string('folder')->toString())->id))
            ->with('folder.vault')
            ->orderBy('title')
            ->get(['id', 'uuid', 'account_id', 'vault_id', 'knowledge_folder_id', 'title', 'created_at', 'updated_at']);

        return response()->json([
            'data' => KnowledgeNoteResource::collection($notes)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $note): JsonResponse
    {
        return new KnowledgeNoteResource($this->note($this->vault($request, $vault), $note))->response();
    }

    public function create(Request $request, string $vault): JsonResponse
    {
        $record = $this->vault($request, $vault);

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:'.KnowledgeNote::MAX_BODY_LENGTH],
            'folder_id' => ['nullable', 'uuid'],
        ]);

        $note = new CreateKnowledgeNote(
            user: $request->user(),
            vault: $record,
            folder: $request->filled('folder_id') ? $this->folder($record, $request->string('folder_id')->toString()) : null,
            title: $request->string('title')->toString(),
            body: $request->string('body')->toString(),
        )->execute();

        return new KnowledgeNoteResource($note)->response()->setStatusCode(201);
    }

    /**
     * Change a note. A field left out of the request keeps what the note says; a text sent as null
     * or empty is cleared.
     */
    public function update(Request $request, string $vault, string $note): JsonResponse
    {
        $record = $this->vault($request, $vault);
        $knowledgeNote = $this->note($record, $note);

        $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string', 'max:'.KnowledgeNote::MAX_BODY_LENGTH],
            'folder_id' => ['sometimes', 'uuid'],
        ]);

        new UpdateKnowledgeNote(
            user: $request->user(),
            note: $knowledgeNote,
            title: $request->exists('title') ? $request->string('title')->toString() : null,
            body: $request->exists('body') ? $request->string('body')->toString() : null,
            folder: $request->filled('folder_id') ? $this->folder($record, $request->string('folder_id')->toString()) : null,
        )->execute();

        return new KnowledgeNoteResource($knowledgeNote->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $note): Response
    {
        new DestroyKnowledgeNote(
            user: $request->user(),
            note: $this->note($this->vault($request, $vault), $note),
        )->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function folder(Vault $vault, string $uuid): KnowledgeFolder
    {
        return KnowledgeFolder::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function note(Vault $vault, string $uuid): KnowledgeNote
    {
        return KnowledgeNote::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
