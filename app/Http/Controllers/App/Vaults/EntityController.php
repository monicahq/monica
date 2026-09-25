<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\Models\Vault;
use App\ViewModels\Vaults\EntitiesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntityController extends Controller
{
    public function index(Request $request, Vault $vault, EntityType $entityType): View
    {
        abort_unless(
            EntityType::query()->whereKey($entityType->id)->visibleInVault($vault)->exists(),
            404,
        );

        return view('app.vaults.entities.index', [
            'viewModel' => new EntitiesViewModel(
                user: $request->user(),
                vault: $vault,
                entityType: $entityType,
            ),
        ]);
    }
}
