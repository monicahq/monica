<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\CheckStripeConnection;
use App\Http\Controllers\Controller;
use App\ViewModels\Admin\BillingStatusViewModel;
use Illuminate\View\View;

class BillingStatusController extends Controller
{
    public function index(): View
    {
        return view('app.admin.billing.index', [
            'viewModel' => new BillingStatusViewModel(
                connection: config('monica.self_hosted') ? null : new CheckStripeConnection()->execute(),
            ),
        ]);
    }
}
