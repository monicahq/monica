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
                'journal_metrics' => route('journal_metrics.index', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
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
        $posts = $journal->posts()
            ->orderBy('written_at', 'desc')
            ->whereYear('written_at', (string) $year)
            ->with('files')
            ->get()
            ->groupBy(fn (Post $post) => $post->written_at->month);

        $monthsCollection = collect();
        for ($month = 12; $month > 0; $month--) {
            $postsCollection = $posts->get($month, collect())
                ->map(fn (Post $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'excerpt' => $post->excerpt,
                    'written_at_day' => Str::upper(DateHelper::formatShortDay($post->written_at)),
                    'written_at_day_number' => DateHelper::formatDayNumber($post->written_at),
                    'photo' => optional(optional($post)->files)->first() ? [
                        'id' => $post->files->first()->id,
                        'url' => [
                            'show' => 'https://ucarecdn.com/'.$post->files->first()->uuid.'/-/scale_crop/75x75/smart/-/format/auto/-/quality/smart_retina/',
                        ],
                    ] : null,
                    'url' => [
                        'show' => route('post.show', [
                            'vault' => $journal->vault_id,
                            'journal' => $journal->id,
                            'post' => $post,
                        ]),
                    ],
                ]);

            $monthsCollection->push([
                'id' => $month,
                'month' => Str::upper(Carbon::createFromDate($year, $month, 1)->format('M')),
                'month_human_format' => DateHelper::formatLongMonthAndYear(Carbon::createFromDate($year, $month, 1)),
                'posts' => $postsCollection,
                'count' => $postsCollection->count(),
                'color' => 'bg-gray-50 dark:bg-gray-900',
            ]);
        }

        // Now we have a collection of months. We need to color each month
        // according to the number of posts they have. The more posts, the darker
        // the color.
        $maxPostsInMonth = 0;
        $maxPosts = 0;
        foreach ($monthsCollection as $month) {
            if ($month['count'] > $maxPostsInMonth) {
                $maxPostsInMonth = $month['count'];
            }

            $maxPosts = $maxPosts + $month['count'];
        }

        foreach ($monthsCollection as $month) {
            if ($month['count'] > 0) {
                $percent = round(($month['count'] / $maxPostsInMonth) * 100);
                // now we round to the nearest 100
                $round = $percent - ($percent % 100 - 100);
                $dark = 1000 - $round;
                $color = "bg-green-$round dark:bg-green-$dark";

                // a really barbaric piece of code so we replace the current collection
                // value with the proper value
                $monthsCollection->transform(function ($item, $key) use ($month, $color) {
                    if ($item['id'] === $month['id']) {
                        $item['color'] = $color;
                    }

                    return $item;
                });
            }
        }

        return $monthsCollection;
    }

    /**
     * Get all the years that have posts in the journal.
     */
    public static function yearsOfContentInJournal(Journal $journal): Collection
    {
        /** @var Collection<int,Post> */
        $posts = Post::where('journal_id', $journal->id)
            ->select(DB::raw(SQLHelper::year('written_at').' as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->get();

        return $posts->map(fn (Post $post): array => [ // @phpstan-ignore-line
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
        return $journal
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
    }
}
