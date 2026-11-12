<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Knowledge;

use App\Actions\CreateKnowledgeFolder;
use App\Actions\DestroyKnowledgeFolder;
use App\Actions\UpdateKnowledgeFolder;
use App\Actions\UpdateKnowledgeFolderPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeFolderResource;
use App\Models\KnowledgeFolder;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class KnowledgeFolderController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $folders = $this->vault($request, $vault)
            ->knowledgeFolders()
            ->with(['vault', 'notes:id,uuid,knowledge_folder_id,title', 'lists:id,uuid,knowledge_folder_id,title'])
            ->get();

        return response()->json([
            'data' => KnowledgeFolderResource::collection($folders)->resolve(),
        ]);
    }

    public function create(Request $request, string $vault): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $folder = new CreateKnowledgeFolder(
            user: $request->user(),
            vault: $this->vault($request, $vault),
            name: $request->string('name')->toString(),
        )->execute();

        return new KnowledgeFolderResource($folder)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $folder): JsonResponse
    {
        $record = $this->folder($this->vault($request, $vault), $folder);

        $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ]);

        if ($request->exists('name')) {
            new UpdateKnowledgeFolder(
                user: $request->user(),
                folder: $record,
                name: $request->string('name')->toString(),
            )->execute();
        }

        if ($request->exists('position')) {
            new UpdateKnowledgeFolderPosition(
                user: $request->user(),
                folder: $record,
                position: $request->integer('position'),
            )->execute();
        }

        return new KnowledgeFolderResource($record->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $folder): Response
    {
        $record = $this->vault($request, $vault);

        $request->validate([
            'move_to' => ['nullable', 'uuid'],
        ]);

        $moveTo = $request->filled('move_to') ? $this->folder($record, $request->string('move_to')->toString()) : null;

        try {
            new DestroyKnowledgeFolder(
                user: $request->user(),
                folder: $this->folder($record, $folder),
                moveTo: $moveTo,
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['folder' => $e->getMessage()]);
        }

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
}
