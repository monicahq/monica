<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\PortalCatalogue;
use App\ViewModels\Docs\PortalDocsViewModel;
use Illuminate\View\View;

class PortalDocsController extends Controller
{
    public function index(string $locale): View
    {
        $catalogue = new PortalCatalogue(locale: $locale);
        $page = $catalogue->root();

        abort_if($page === null, 404);

        return view('app.docs.portal', [
            'viewModel' => new PortalDocsViewModel(catalogue: $catalogue, page: $page),
        ]);
    }

    public function show(string $locale, string $section, ?string $slug = null): View
    {
        $catalogue = new PortalCatalogue(locale: $locale);
        $page = $catalogue->find(section: $section, slug: $slug);

        abort_if($page === null, 404);

        return view('app.docs.portal', [
            'viewModel' => new PortalDocsViewModel(catalogue: $catalogue, page: $page),
        ]);
    }
}
