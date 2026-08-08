<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\EntityTypes;

use App\Actions\UpdateEntityTypePosition;
use App\Http\Controllers\Controller;
use App\Models\EntityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EntityTypePositionController extends Controller
{
    public function update(Request $request, EntityType $entityType): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateEntityTypePosition(
            user: $request->user(),
            entityType: $entityType,
            position: $request->integer('position'),
        )->execute();

        return redirect()->route('settings.entityTypes.index')
            ->with('status', __('The order is saved.'))
            ->with('status_description', __('The types appear in the vault navigation in the order you put them in.'));
    }
}
