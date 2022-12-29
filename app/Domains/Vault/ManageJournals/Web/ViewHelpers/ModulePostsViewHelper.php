<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class ModulePostsViewHelper
{
    public static function data(Contact $contact, User $user): Collection
    {
        return $contact->posts()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Post $post) => self::dto($post, $user));
    }

    public static function dto(Post $post, User $user): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'journal' => [
                'id' => $post->journal->id,
                'name' => $post->journal->name,
                'url' => [
                    'show' => route('journal.show', [
                        'vault' => $post->journal->vault->id,
                        'journal' => $post->journal->id,
                    ]),
                ],
            ],
            'written_at' => DateHelper::format($post->written_at, $user),
            'url' => [
                'show' => route('post.show', [
                    'vault' => $post->journal->vault_id,
                    'journal' => $post->journal->id,
                    'post' => $post->id,
                ]),
            ],
        ];
    }
}
