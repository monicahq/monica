<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\PossessionType;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionTypeFieldsBlock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PossessionTypeFieldController extends Controller
{
    public function index(Request $request, Vault $vault, PossessionType $possessionType): View
    {
        abort_unless($possessionType->account_id === $request->user()->account_id, 404);

        return view('app.vaults.possessions._type-fields', [
            'fieldsBlock' => new PossessionTypeFieldsBlock(possessionType: $possessionType),
        ]);
    }
}
