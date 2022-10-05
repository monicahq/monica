<?php

namespace App\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\PostHelper;
use App\Models\Journal;
use App\Models\Post;
use App\Models\PostSection;

class PostEditViewHelper
{
    public static function data(Journal $journal, Post $post): array
    {
        $sectionsCollection = $post->postSections()
            ->orderBy('position')
            ->get()
            ->map(fn (PostSection $postSection) => [
                'id' => $postSection->id,
                'label' => $postSection->label,
                'content' => $postSection->content,
            ]);

        return [
            'id' => $post->id,
            'title' => $post->title,
            'sections' => $sectionsCollection,
            'statistics' => PostHelper::statistics($post),
            'journal' => [
                'name' => $journal->name,
            ],
            'url' => [
                'update' => route('post.update', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                ]),
                'back' => route('journal.show', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }
}
