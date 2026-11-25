<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Helpers\KnowledgeMarkdown;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeNote;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KnowledgeNoteMarkdownController extends Controller
{
    public function show(Request $request, Vault $vault, KnowledgeNote $knowledgeNote): StreamedResponse
    {
        $document = KnowledgeMarkdown::document($knowledgeNote->title, $knowledgeNote->body);
        $name = Str::slug($knowledgeNote->title) ?: 'note';

        return response()->streamDownload(
            function () use ($document): void {
                echo $document;
            },
            "{$name}.md",
            ['Content-Type' => 'text/markdown; charset=utf-8'],
        );
    }
}
