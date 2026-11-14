<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\RemovePreviewListEntry;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\PreviewListEntry;
use App\ViewModels\Admin\PreviewListViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewListEntryController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.preview-list.index', [
            'viewModel' => new PreviewListViewModel(
                size: PageSize::from($request, PreviewListViewModel::PER_PAGE),
            ),
        ]);
    }

    public function destroy(Request $request, PreviewListEntry $previewListEntry): RedirectResponse
    {
        new RemovePreviewListEntry(
            administrator: $request->user(),
            entry: $previewListEntry,
        )->execute();

        return redirect()->route('admin.previewList.index')
            ->with('status', __(':email is off the waiting list.', ['email' => $previewListEntry->email]));
    }
}
