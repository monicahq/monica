<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WikipediaHelper
{
    /**
     * Return the information about the given city or country from Wikipedia.
     * All API calls are documented here:
     * https://www.mediawiki.org/w/api.php?action=help&modules=query.
     *
     * @param  string  $topic
     * @return array
     */
    public static function getInformation(string $topic): array
    {
        $query = http_build_query([
            'action' => 'query',
            'prop' => 'description|pageimages',
            'titles' => $topic,
            'pithumbsize' => 400,
            'format' => 'json',
        ]);

        $url = 'https://en.wikipedia.org/w/api.php?'.$query;

        $response = Http::get($url)->throw();

        if ($response->json('query.pages.*.missing')[0] === true) {
            return [
                'url' => null,
                'description' => null,
                'thumbnail' => null,
            ];
        }

        return [
            'url' => 'https://en.wikipedia.org/wiki/'.Str::slug($topic),
            'description' => $response->json('query.pages.*.description')[0],
            'thumbnail' => $response->json('query.pages.*.thumbnail.source')[0],
        ];
    }
}
