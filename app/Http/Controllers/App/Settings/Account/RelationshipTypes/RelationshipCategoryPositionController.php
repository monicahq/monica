<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\RelationshipTypes;

use App\Actions\UpdateRelationshipCategoryPosition;
use App\Http\Controllers\Controller;
use App\Models\RelationshipCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RelationshipCategoryPositionController extends Controller
{
    public function update(Request $request, RelationshipCategory $relationshipCategory): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateRelationshipCategoryPosition(
            user: $request->user(),
            relationshipCategory: $relationshipCategory,
            position: $request->integer('position'),
        )->execute();

        return redirect()->route('settings.relationshipTypes.index')
            ->with('status', __('The order is saved.'));
    }
}
