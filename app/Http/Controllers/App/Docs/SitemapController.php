<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\DocsViewModel;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        abort_unless((bool) config('monica.search_engine_indexing'), 404);

        return response(new DocsViewModel()->sitemap(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
