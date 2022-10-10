<?php

namespace App\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\User;

class PostShowViewHelper
{
    public static function data(Post $post, User $user): array
    {
        $sections = $post->postSections()
            ->orderBy('position')
            ->whereNotNull('content')
            ->get()
            ->map(fn (PostSection $section) => [
                'id' => $section->id,
                'label' => $section->label,
                'content' => $section->content,
            ]);

        return [
            'id' => $post->id,
            'title' => $post->title,
            'title_exists' => $post->title === trans('app.undefined') ? false : true,
            'written_at' => DateHelper::format($post->written_at, $user),
            'published' => $post->published,
            'sections' => $sections,
            'journal' => [
                'name' => $post->journal->name,
                'url' => [
                    'show' => route('journal.show', [
                        'vault' => $post->journal->vault_id,
                        'journal' => $post->journal->id,
                    ]),
                ],
            ],
            'url' => [
                'edit' => route('post.edit', [
                    'vault' => $post->journal->vault_id,
                    'journal' => $post->journal->id,
                    'post' => $post->id,
                ]),
                'back' => route('journal.show', [
                    'vault' => $post->journal->vault_id,
                    'journal' => $post->journal->id,
                ]),
            ],
        ];
    }
}
