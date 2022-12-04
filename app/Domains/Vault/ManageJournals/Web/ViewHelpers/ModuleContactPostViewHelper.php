<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Post;
use Illuminate\Support\Collection;

class ModuleContactPostViewHelper
{
    public static function data(Contact $contact): Collection
    {
        return $contact->posts()
            ->orderBy('written_at', 'desc')
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'url' => [
                    'show' => route('post.show', [
                        'vault' => $contact->vault_id,
                        'journal' => $post->journal_id,
                        'post' => $post->id,
                    ]),
                ],
            ]);
    }
}
