<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Marketplace;

use App\Http\Controllers\Controller;
use App\ViewModels\Marketplace\MarketplaceViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The catalogue, which is the one screen of the product somebody can read
 * without an account. A link to a template has to work when it is pasted into a
 * forum, so the user is whoever happens to be signed in and may be nobody.
 */
class MarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.marketplace.index', [
            'viewModel' => new MarketplaceViewModel(
                user: $request->user(),
                query: (string) $request->string('query'),
                type: (string) $request->string('type', MarketplaceViewModel::TYPE_ALL),
                category: (string) $request->string('category'),
                locale: (string) $request->string('locale'),
                sort: (string) $request->string('sort', MarketplaceViewModel::SORT_UPDATED),
            ),
        ]);
    }
}
