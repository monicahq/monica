<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\Vault;
use App\ViewModels\Vaults\FieldChoicesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldChoiceController extends Controller
{
    public function index(Request $request, Vault $vault, Field $field): View
    {
        abort_unless($field->account_id === $request->user()->account_id && $field->type->isRelation(), 404);

        return view('app.vaults.fields.choices', [
            'viewModel' => new FieldChoicesViewModel(
                user: $request->user(),
                vault: $vault,
                field: $field,
                query: $request->string('query')->limit(255, '')->toString(),
            ),
        ]);
    }
}
