<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\PostHelper;
use App\Models\Journal;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\Tag;

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

        $tagsInVault = $journal->vault->tags;
        $tagsInPost = $post->tags;

        $tagsInVaultCollection = $tagsInVault->map(function ($tag) use ($tagsInPost, $journal, $post) {
            $taken = false;
            if ($tagsInPost->contains($tag)) {
                $taken = true;
            }

            return self::dtoTag($journal, $post, $tag, $taken);
        });

        $tagsAssociatedWithPostCollection = $tagsInPost->map(function ($tag) use ($journal, $post) {
            return self::dtoTag($journal, $post, $tag);
        });

        return [
            'id' => $post->id,
            'title' => $post->title,
            'sections' => $sectionsCollection,
            'statistics' => PostHelper::statistics($post),
            'tags_in_post' => $tagsAssociatedWithPostCollection,
            'tags_in_vault' => $tagsInVaultCollection,
            'journal' => [
                'name' => $journal->name,
            ],
            'url' => [
                'update' => route('post.update', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                ]),
                'show' => route('post.show', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                ]),
                'tag_store' => route('post.tag.store', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                ]),
                'back' => route('journal.show', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
                'destroy' => route('post.destroy', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                ]),
            ],
        ];
    }

    public static function dtoTag(Journal $journal, Post $post, Tag $tag, bool $taken = false): array
    {
        return [
            'id' => $tag->id,
            'name' => $tag->name,
            'taken' => $taken,
            'url' => [
                'update' => route('post.tag.update', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                    'tag' => $tag->id,
                ]),
                'destroy' => route('post.tag.destroy', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                    'tag' => $tag->id,
                ]),
            ],
        ];
    }
}
