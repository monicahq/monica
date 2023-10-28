<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\FileHelper;
use App\Helpers\PostHelper;
use App\Helpers\StorageHelper;
use App\Models\Contact;
use App\Models\File;
use App\Models\Journal;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\PostSection;
use App\Models\SliceOfLife;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Collection;

class PostEditViewHelper
{
    public static function data(Journal $journal, Post $post, User $user): array
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

        $contacts = $post->contacts()
            ->get()
            ->map(fn (Contact $contact) => self::dtoContact($contact));

        $slices = $journal->slicesOfLife()->get()->map(fn (SliceOfLife $slice) => [
            'id' => $slice->id,
            'name' => $slice->name,
        ]);

        $photos = $post->files()
            ->where('type', File::TYPE_PHOTO)
            ->get()
            ->map(fn (File $file) => self::dtoPhoto($journal, $post, $file));

        return [
            'id' => $post->id,
            'title' => $post->title,
            'date' => DateHelper::format($post->written_at, $user),
            'editable_date' => $post->written_at->format('Y-m-d'),
            'sections' => $sectionsCollection,
            'contacts' => $contacts,
            'photos' => $photos,
            'slice' => $post->sliceOfLife ? self::dtoSlice($journal, $post->sliceOfLife) : null,
            'slices' => $slices,
            'statistics' => PostHelper::statistics($post),
            'tags_in_post' => $tagsAssociatedWithPostCollection,
            'tags_in_vault' => $tagsInVaultCollection,
            'journal_metrics' => self::journalMetrics($post),
            'uploadcare' => StorageHelper::uploadcare(),
            'canUploadFile' => StorageHelper::canUploadFile($journal->vault->account),
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
                'slice_store' => route('post.slices.update', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                ]),
                'slice_reset' => route('post.slices.destroy', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                ]),
                'upload_photo' => route('post.photos.store', [
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

    public static function dtoContact(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'avatar' => $contact->avatar,
            'url' => [
                'show' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dtoSlice(Journal $journal, SliceOfLife $slice): array
    {
        return [
            'id' => $slice->id,
            'name' => $slice->name,
            'url' => [
                'show' => route('slices.show', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'slice' => $slice->id,
                ]),
            ],
        ];
    }

    public static function dtoPhoto(Journal $journal, Post $post, File $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'size' => FileHelper::formatFileSize($file->size),
            'mime_type' => $file->mime_type,
            'url' => [
                'show' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/75x75/smart/-/format/auto/-/quality/smart_retina/',
                'destroy' => route('post.photos.destroy', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'post' => $post->id,
                    'photo' => $file->id,
                ]),
            ],
        ];
    }

    public static function journalMetrics(Post $post): Collection
    {
        $journalMetrics = $post->journal->journalMetrics;

        $collection = collect([]);
        foreach ($journalMetrics as $journalMetric) {
            $postMetrics = $post->postMetrics()
                ->where('journal_metric_id', $journalMetric->id)
                ->get()
                ->map(fn (PostMetric $postMetric) => self::dtoPostMetric($postMetric));

            $collection->push([
                'id' => $journalMetric->id,
                'label' => $journalMetric->label,
                'post_metrics' => $postMetrics,
                'url' => [
                    'store' => route('post.metrics.store', [
                        'vault' => $post->journal->vault_id,
                        'journal' => $post->journal_id,
                        'post' => $post->id,
                    ]),
                ],
            ]);
        }

        return $collection;
    }

    public static function dtoPostMetric(PostMetric $postMetric): array
    {
        return [
            'id' => $postMetric->id,
            'value' => $postMetric->value,
            'label' => $postMetric->label,
            'url' => [
                'destroy' => route('post.metrics.destroy', [
                    'vault' => $postMetric->post->journal->vault_id,
                    'journal' => $postMetric->post->journal_id,
                    'post' => $postMetric->post->id,
                    'metric' => $postMetric->id,
                ]),
            ],
        ];
    }
}
