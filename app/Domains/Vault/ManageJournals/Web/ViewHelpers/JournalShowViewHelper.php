<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\SliceOfLifeHelper;
use App\Helpers\SQLHelper;
use App\Models\Journal;
use App\Models\Post;
use App\Models\SliceOfLife;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalShowViewHelper
{
    public static function data(Journal $journal, int $year, User $user): array
    {
        $monthsCollection = self::postsInYear($journal, $year, $user);

        return [
            'id' => $journal->id,
            'name' => $journal->name,
            'description' => $journal->description,
            'months' => $monthsCollection,
            'years' => self::yearsOfContentInJournal($journal),
            'tags' => self::tags($journal),
            'slices' => self::slices($journal),
            'url' => [
                'photo_index' => route('journal.photo.index', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
                'edit' => route('journal.edit', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
                'destroy' => route('journal.destroy', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
                'create' => route('post.create', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
                'slice_index' => route('slices.index', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }

    /**
     * Get all the posts in the given year, ordered by month descending.
     *
     *
     * @psalm-suppress NoValue
     */
    public static function postsInYear(Journal $journal, int $year, User $user): Collection
    {
        $monthsCollection = collect();
        for ($month = 12; $month > 0; $month--) {
            $postsCollection = collect();

            $posts = $journal->posts()
                ->orderBy('written_at', 'desc')
                ->whereYear('written_at', (string) $year)
                ->whereMonth('written_at', (string) $month)
                ->get();

            foreach ($posts as $post) {
                $postsCollection->push([
                    'id' => $post->id,
                    'title' => $post->title,
                    'excerpt' => $post->excerpt,
                    'written_at_day' => Str::upper(DateHelper::formatShortDay($post->written_at)),
                    'written_at_day_number' => DateHelper::formatDayNumber($post->written_at),
                    'url' => [
                        'show' => route('post.show', [
                            'vault' => $journal->vault_id,
                            'journal' => $journal->id,
                            'post' => $post->id,
                        ]),
                    ],
                ]);
            }

            $monthsCollection->push([
                'id' => $month,
                'month' => Str::upper(Carbon::createFromDate($year, $month, 1)->format('M')),
                'month_human_format' => DateHelper::formatLongMonthAndYear(Carbon::createFromDate($year, $month, 1)),
                'posts' => $postsCollection,
                'color' => 'bg-green-',
            ]);
        }

        // Now we have a collection of months. We need to color each month
        // according to the number of posts they have. The more posts, the darker
        // the color.
        $maxPostsInMonth = 0;
        $maxPosts = 0;
        foreach ($monthsCollection as $month) {
            if ($month['posts']->count() > $maxPostsInMonth) {
                $maxPostsInMonth = $month['posts']->count();
            }

            $maxPosts = $maxPosts + $month['posts']->count();
        }

        foreach ($monthsCollection as $month) {
            if ($month['posts']->count() === 0) {
                $color = 'bg-gray-50';
            } else {
                $percent = round(($month['posts']->count() / $maxPostsInMonth) * 100);
                // now we round to the nearest 100
                $round = $percent - ($percent % 100 - 100);
                $color = 'bg-green-'.(string) $round;
            }

            // a really barbaric piece of code so we replace the current collection
            // value with the proper value
            $monthsCollection->transform(function ($item, $key) use ($month, $color) {
                if ($item['id'] === $month['id']) {
                    $item['color'] = $color;
                }

                return $item;
            });
        }

        return $monthsCollection;
    }

    /**
     * Get all the years that have posts in the journal.
     */
    public static function yearsOfContentInJournal(Journal $journal): Collection
    {
        $posts = Post::where('journal_id', $journal->id)
            ->select(DB::raw(SQLHelper::year('written_at').' as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->get();

        return $posts->map(fn (Post $post) => [
            'year' => $post->year,
            'posts' => Post::where('journal_id', $journal->id)
                ->whereYear('written_at', $post->year)
                ->count(),
            'url' => [
                'show' => route('journal.year', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                    'year' => $post->year,
                ]),
            ],
        ]);
    }

    public static function tags(Journal $journal): Collection
    {
        // this is not optimized
        $posts = $journal->posts->pluck('id')->toArray();

        $tags = DB::table('post_tag')
            ->whereIn('post_id', $posts)
            ->get()
            ->unique('tag_id')
            ->toArray();

        $tagsCollection = collect();
        foreach ($tags as $tag) {
            $tag = Tag::with('posts')->find($tag->tag_id);

            $tagsCollection->push([
                'id' => $tag->id,
                'name' => $tag->name,
                'count' => $tag->posts()->count(),
            ]);
        }

        return $tagsCollection;
    }

    public static function slices(Journal $journal): Collection
    {
        $slicesCollection = $journal
            ->slicesOfLife()
            ->get()
            ->map(fn (SliceOfLife $slice) => [
                'id' => $slice->id,
                'name' => $slice->name,
                'date_range' => SliceOfLifeHelper::getDateRange($slice),
                'cover_image' => $slice->file ? 'https://ucarecdn.com/'.$slice->file->uuid.'/-/scale_crop/200x100/smart/-/format/auto/-/quality/smart_retina/' : null,
                'url' => [
                    'show' => route('slices.show', [
                        'vault' => $journal->vault_id,
                        'journal' => $journal->id,
                        'slice' => $slice->id,
                    ]),
                ],
            ]);

        return $slicesCollection;
    }
}
