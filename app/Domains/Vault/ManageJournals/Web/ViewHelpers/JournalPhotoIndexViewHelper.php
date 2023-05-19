<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\File;
use App\Models\Journal;
use App\Models\Post;

class JournalPhotoIndexViewHelper
{
    public static function data(Journal $journal): array
    {
        // we get all the posts which have a photo in this journal, sorted by date
        $posts = $journal->posts()
            ->with('files')
            ->whereHas('files', function ($query) {
                $query->where('type', File::TYPE_PHOTO);
            })
            ->orderBy('written_at', 'desc')
            ->get();

        $photos = collect([]);
        foreach ($posts as $post) {
            foreach ($post->files as $photo) {
                $photos->push(self::dto($photo, $post));
            }
        }

        return [
            'id' => $journal->id,
            'name' => $journal->name,
            'description' => $journal->description,
            'photos' => $photos,
            'url' => [
                'show' => route('journal.show', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }

    public static function dto(File $file, Post $post): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'url' => [
                'post' => route('post.show', [
                    'vault' => $post->journal->vault_id,
                    'journal' => $post->journal->id,
                    'post' => $post->id,
                ]),
                'display' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/200x200/smart/-/format/auto/-/quality/smart_retina/',
            ],
        ];
    }
}
