<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\ViewModels\Admin\AdministratorLogsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdministratorLogController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.logs.index', [
            'viewModel' => new AdministratorLogsViewModel(
                viewer: $request->user(),
                administratorId: $request->integer('administrator') ?: null,
                accountId: $request->integer('account') ?: null,
                size: PageSize::from($request, AdministratorLogsViewModel::PER_PAGE),
            ),
        ]);
    }
}
