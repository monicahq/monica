<?php

namespace App\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Journal;
use App\Models\Post;
use App\Models\User;

class JournalShowViewHelper
{
    public static function data(Journal $journal, User $user): array
    {
        $postsCollection = $journal->posts()
            ->orderBy('written_at', 'desc')
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'written_at' => DateHelper::format($post->written_at, $user),
                'url' => [
                    'show' => route('post.show', [
                        'vault' => $journal->vault_id,
                        'journal' => $journal->id,
                        'post' => $post->id,
                    ]),
                ],
            ]);

        return [
            'id' => $journal->id,
            'name' => $journal->name,
            'description' => $journal->description,
            'posts' => $postsCollection,
            'url' => [
                'create' => route('post.create', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }
}
