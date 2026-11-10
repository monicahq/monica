<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\AddGroupFile;
use App\Actions\RemoveGroupFile;
use App\Exceptions\NotEnoughStorageLeft;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupFileResource;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class GroupFileController extends Controller
{
    public function index(Request $request, string $vault, string $group): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $attachments = GroupFile::query()
            ->where('group_id', $group->id)
            ->with(GroupFileResource::RELATIONS)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => array_map(
                fn (GroupFile $attachment): array => new GroupFileResource($attachment)->resolve($request),
                $attachments->items(),
            ),
            'meta' => [
                'page' => $attachments->currentPage(),
                'per_page' => $attachments->perPage(),
                'total' => $attachments->total(),
            ],
        ]);
    }

    public function show(Request $request, string $vault, string $group, string $file): JsonResponse
    {
        $attachment = $this->attachment($this->group($this->vault($request, $vault), $group), $file);

        return new GroupFileResource($attachment)->response()->setStatusCode(200);
    }

    public function create(Request $request, string $vault, string $group): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('monica.max_file_size_in_kilobytes')],
        ]);

        try {
            $attachment = new AddGroupFile(
                user: $request->user(),
                vault: $vault,
                group: $group,
                upload: $validated['file'],
            )->execute();
        } catch (NotEnoughStorageLeft $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return new GroupFileResource($attachment->load(GroupFileResource::RELATIONS))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $vault, string $group, string $file): Response
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);

        new RemoveGroupFile(
            user: $request->user(),
            vault: $vault,
            group: $group,
            attachment: $this->attachment($group, $file),
        )->execute();

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

    private function attachment(Group $group, string $address): GroupFile
    {
        return GroupFile::query()
            ->where('group_id', $group->id)
            ->where('uuid', $address)
            ->with(GroupFileResource::RELATIONS)
            ->firstOrFail();
    }
}
