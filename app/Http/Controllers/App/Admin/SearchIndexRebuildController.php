<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\StartSearchIndexRebuild;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SearchIndexRebuildController extends Controller
{
    public function create(Request $request, string $kind): RedirectResponse
    {
        new StartSearchIndexRebuild(
            kind: $kind,
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.search.index')
            ->with('status', __('The rebuild has started.'))
            ->with('status_description', __('Search misses records of this kind until it has finished.'));
    }
}
