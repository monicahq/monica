<?php

namespace App\Helpers;

use App\Models\Post;
use Illuminate\Support\Str;

class PostHelper
{
    /**
     * Get the statistics of the post.
     */
    public static function statistics(Post $post): array
    {
        $wordCount = 0;
        $duration = 0;

        $postSections = $post->postSections()
            ->whereNotNull('content')
            ->get();

        foreach ($postSections as $postSection) {
            $wordCount = $wordCount + Str::of($postSection->content)->wordCount();
        }

        // human read around 200 words per minute
        $minutesToRead = round($wordCount / 200);
        $duration = (int) max(1, $minutesToRead);

        return [
            'word_count' => $wordCount,
            'time_to_read_in_minute' => $duration,
            'view_count' => $post->view_count,
        ];
    }
}
