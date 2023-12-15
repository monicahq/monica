<?php

namespace App\Domains\Contact\ManagePhotos\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Http;

class GooglePhoto extends BaseService implements ServiceInterface
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
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a pet.
     *
     * @throws Exception
     */
    public function execute(string $searchTerm): array
    {
        $html = $this->search($searchTerm);

        return $this->imageUrls($html);
    }

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
    private function imageUrls(string $html): array
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
    private function search(string $searchTerm): string
    {
        $params = array_merge($this->params, [
            'q' => $searchTerm,
        ]);

        try {
            $response = Http::get(self::GOOGLE_SEARCH_URL, $params);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch data from Google.');
        }

        return $response->body() ?? '';
    }
}
