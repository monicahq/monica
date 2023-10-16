<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\DateHelper;
use App\Helpers\SliceOfLifeHelper;
use App\Helpers\StorageHelper;
use App\Models\Post;
use App\Models\SliceOfLife;
use Illuminate\Support\Str;

class SliceOfLifeShowViewHelper
{
    public static function data(SliceOfLife $slice): array
    {
        $posts = $slice->posts()->with('contacts')->orderBy('written_at', 'desc')->get();

        // get the details of the posts
        $postsCollection = $posts->map(fn (Post $post) => [
            'id' => $post->id,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'written_at_day' => Str::upper(DateHelper::formatShortDay($post->written_at)),
            'written_at_day_number' => DateHelper::formatDayNumber($post->written_at),
            'written_at' => DateHelper::formatDate($post->written_at),
            'url' => [
                'show' => route('post.show', [
                    'vault' => $post->journal->vault_id,
                    'journal' => $post->journal_id,
                    'post' => $post->id,
                ]),
            ],
        ]);

        // get the contacts in the posts
        $contactsCollection = collect();
        foreach ($posts as $post) {
            $contacts = $post->contacts;

            foreach ($contacts as $contact) {
                $contactsCollection->push(ContactCardHelper::data($contact));
            }
        }
        $contactsCollection = $contactsCollection->unique('id');

        return [
            'journal' => [
                'id' => $slice->journal->id,
                'name' => $slice->journal->name,
                'url' => [
                    'show' => route('journal.show', [
                        'vault' => $slice->journal->vault_id,
                        'journal' => $slice->journal->id,
                    ]),
                ],
            ],
            'slice' => self::dtoSlice($slice),
            'posts' => $postsCollection,
            'contacts' => $contactsCollection,
            'uploadcare' => StorageHelper::uploadcare(),
            'canUploadFile' => StorageHelper::canUploadFile($slice->journal->vault->account),
            'url' => [
                'slices_index' => route('slices.index', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                ]),
            ],
        ];
    }

    public static function dtoSlice(SliceOfLife $slice): array
    {
        return [
            'id' => $slice->id,
            'name' => $slice->name,
            'description' => $slice->description,
            'date_range' => SliceOfLifeHelper::getDateRange($slice),
            'cover_image' => $slice->file ? 'https://ucarecdn.com/'.$slice->file->uuid.'/-/scale_crop/800x100/smart/-/format/auto/-/quality/smart_retina/' : null,
            'url' => [
                'show' => route('slices.show', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                    'slice' => $slice->id,
                ]),
                'edit' => route('slices.edit', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                    'slice' => $slice->id,
                ]),
                'update_cover_image' => route('slices.cover.update', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                    'slice' => $slice->id,
                ]),
                'destroy_cover_image' => route('slices.cover.destroy', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                    'slice' => $slice->id,
                ]),
                'destroy' => route('slices.destroy', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                    'slice' => $slice->id,
                ]),
            ],
        ];
    }
}
