<?php

namespace App\Services;

use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Http;

class GooglePhotoService
{
    /**
     * Google search URL.
     */
    public const GOOGLE_SEARCH_URL = 'https://www.google.com/search';

    /**
     * The params for Google search.
     */
    private array $params = [
        'tbm' => 'isch',
        'tbs' => 'iar:xw,ift:png',
    ];

    /**
     * Set the params for the service.
     */
    public function params(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Extract image URLs from the HTML.
     */
    public function imageUrls(string $html): array
    {
        $imageUrls = [];

        if (empty($html)) {
            return $imageUrls;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        $imgTags = $doc->getElementsByTagName('img');

        foreach ($imgTags as $imgTag) {
            $src = $imgTag->getAttribute('src');
            if (filter_var($src, FILTER_VALIDATE_URL)) {
                $imageUrls[] = $src;
            }
        }

        return $imageUrls;
    }

    /**
     * Fetch the HTML from Google.
     *
     * @throws Exception
     */
    public function search(string $searchTerm): array
    {
        $params = array_merge($this->params, [
            'q' => $searchTerm,
        ]);

        try {
            $html = Http::get(self::GOOGLE_SEARCH_URL, $params)->body();
        } catch (Exception $e) {
            throw new Exception('Failed to fetch data from Google.');
        }

        return $this->imageUrls($html);
    }
}
