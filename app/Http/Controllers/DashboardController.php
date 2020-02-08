<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Contact\Debt;
use Illuminate\Http\Request;
use function Safe\json_encode;
use App\Helpers\InstanceHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Debt\Debt as DebtResource;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Dashboard/Index', []);
    }
}
