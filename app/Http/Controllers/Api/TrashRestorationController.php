<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\RestoreTrashItem;
use App\Enums\TrashItemKindEnum;
use App\Exceptions\CannotRestoreFromTheTrash;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrashRestorationController extends Controller
{
    public function update(Request $request, string $vault, string $kind, string $item): JsonResponse
    {
        $restoration = new RestoreTrashItem(
            user: $request->user(),
            vault: $this->vault($request, $vault),
            kind: TrashItemKindEnum::from($kind),
            uuid: $item,
        );

        try {
            $restored = $restoration->execute();
        } catch (CannotRestoreFromTheTrash $e) {
            throw ValidationException::withMessages(['item' => $e->getMessage()]);
        }

        return response()->json([
            'data' => [
                'type' => 'trash_item',
                'id' => $restored->uuid(),
                'attributes' => [
                    'kind' => $restored->kind->value,
                    'title' => $restored->title(),
                    'restored_with' => array_map(
                        fn (string $kind, int $count): array => ['kind' => $kind, 'count' => $count],
                        array_keys($restoration->broughtBack()),
                        array_values($restoration->broughtBack()),
                    ),
                ],
            ],
        ]);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }
}
