<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\UpdatePossessionFeatured;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PossessionFeaturedController extends Controller
{
    public function update(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $request->validate([
            'is_featured' => ['required', 'boolean'],
        ]);

        $possession = new UpdatePossessionFeatured(
            user: $request->user(),
            possession: $possession,
            isFeatured: $request->boolean('is_featured'),
        )->execute();

        $status = $possession->is_featured
            ? __(':name is featured.', ['name' => $possession->name])
            : __(':name is no longer featured.', ['name' => $possession->name]);

        return redirect()
            ->route('possessions.show', [$vault, $possession])
            ->with('status', $status);
    }
}
