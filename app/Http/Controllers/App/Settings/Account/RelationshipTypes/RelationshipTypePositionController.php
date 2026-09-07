<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\RelationshipTypes;

use App\Actions\UpdateRelationshipTypePosition;
use App\Http\Controllers\Controller;
use App\Models\RelationshipType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RelationshipTypePositionController extends Controller
{
    public function update(Request $request, RelationshipType $relationshipType): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateRelationshipTypePosition(
            user: $request->user(),
            relationshipType: $relationshipType,
            position: $request->integer('position'),
        )->execute();

        return redirect()->route('settings.relationshipTypes.index')
            ->with('status', __('The order is saved.'))
            ->with('status_description', __('The types are offered in the order you put them in.'));
    }
}
