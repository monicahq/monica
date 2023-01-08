<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\DateHelper;
use App\Helpers\SliceOfLifeHelper;
use App\Models\Contact;
use App\Models\File;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\Tag;
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

        $tags = $post->tags()
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]);

        $contacts = $post->contacts()
            ->get()
            ->map(fn (Contact $contact) => ContactCardHelper::data($contact));

        $photos = $post->files()
            ->where('type', File::TYPE_PHOTO)
            ->get()
            ->map(fn (File $file) => [
                'id' => $file->id,
                'name' => $file->name,
                'url' => [
                    'display' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/100x100/smart/-/format/auto/-/quality/smart_retina/',
                ],
            ]);

        return [
            'id' => $post->id,
            'title' => $post->title,
            'title_exists' => $post->title === trans('app.undefined') ? false : true,
            'written_at' => DateHelper::format($post->written_at, $user),
            'published' => $post->published,
            'sections' => $sections,
            'tags' => $tags,
            'contacts' => $contacts,
            'photos' => $photos,
            'journal' => [
                'name' => $post->journal->name,
                'url' => [
                    'show' => route('journal.show', [
                        'vault' => $post->journal->vault_id,
                        'journal' => $post->journal->id,
                    ]),
                ],
            ],
            'sliceOfLife' => $post->sliceOfLife ? [
                'id' => $post->sliceOfLife->id,
                'name' => $post->sliceOfLife->name,
                'date_range' => SliceOfLifeHelper::getDateRange($post->sliceOfLife),
                'url' => [
                    'show' => route('slices.show', [
                        'vault' => $post->journal->vault_id,
                        'journal' => $post->journal->id,
                        'slice' => $post->sliceOfLife->id,
                    ]),
                ],
            ] : null,
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
