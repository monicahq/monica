<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\DestroyTrashItem;
use App\Enums\TrashItemKindEnum;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrashItemController extends Controller
{
    public function destroy(Request $request, string $vault, string $kind, string $item): Response
    {
        new DestroyTrashItem(
            user: $request->user(),
            vault: $this->vault($request, $vault),
            kind: TrashItemKindEnum::from($kind),
            uuid: $item,
        )->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }
}
