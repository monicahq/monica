<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\PortalCatalogue;
use App\ViewModels\Docs\PortalDocsViewModel;
use Illuminate\Http\Response;

class PortalDocsMarkdownController extends Controller
{
    public function index(string $locale): Response
    {
        $catalogue = new PortalCatalogue(locale: $locale);
        $page = $catalogue->root();

        abort_if($page === null, 404);

        $markdown = new PortalDocsViewModel(catalogue: $catalogue, page: $page)->markdown();

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function show(string $locale, string $section, ?string $slug = null): Response
    {
        $catalogue = new PortalCatalogue(locale: $locale);
        $page = $catalogue->find(section: $section, slug: $slug);

        abort_if($page === null, 404);

        $markdown = new PortalDocsViewModel(catalogue: $catalogue, page: $page)->markdown();

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }
}
