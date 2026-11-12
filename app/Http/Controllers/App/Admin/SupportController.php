<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Helpers\PageSize;
use App\Helpers\SupportQueue;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\ViewModels\Admin\SupportQueueViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.support.index', [
            'viewModel' => new SupportQueueViewModel(
                administrator: $request->user(),
                queue: $this->queue($request),
                size: PageSize::from($request, SupportQueueViewModel::PER_PAGE),
            ),
        ]);
    }

    public function show(Request $request, string $supportRequest): View
    {
        return view('app.admin.support.index', [
            'viewModel' => new SupportQueueViewModel(
                administrator: $request->user(),
                queue: $this->queue($request),
                size: PageSize::from($request, SupportQueueViewModel::PER_PAGE),
                selected: SupportRequest::query()->where('uuid', $supportRequest)->firstOrFail(),
            ),
        ]);
    }

    private function queue(Request $request): SupportQueue
    {
        return new SupportQueue(
            view: (string) $request->string('view'),
            search: (string) $request->string('q'),
            category: (string) $request->string('category'),
            user: (string) $request->string('user'),
            age: (string) $request->string('age'),
        );
    }
}
