<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ChangelogEntry;
use App\Helpers\ChangelogScreenshot;
use App\Helpers\KnowledgeMarkdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @property array{entry: ChangelogEntry, isSeen: bool, myVote: string|null, commentCount: int} $resource */
class ChangelogEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $entry = $this->resource['entry'];

        return [
            'type' => 'changelog_entry',
            'id' => $entry->id,
            'attributes' => [
                'entry_type' => $entry->type->value,
                'area' => $entry->area === null ? null : __($entry->area),
                'title' => __($entry->title),
                'body' => __($entry->body),
                'body_html' => KnowledgeMarkdown::toHtml(__($entry->body)),
                'link' => $entry->link === null ? null : url($entry->link),
                'screenshots' => array_map(fn (ChangelogScreenshot $screenshot): array => [
                    'position' => $screenshot->position,
                    'url' => $screenshot->url(),
                    'description' => __($screenshot->description),
                    'width' => $screenshot->width,
                    'height' => $screenshot->height,
                ], $entry->screenshots),
                'day' => $entry->day(),
                'is_seen' => $this->resource['isSeen'],
                'my_vote' => $this->resource['myVote'],
                'comment_count' => $this->resource['commentCount'],
                'published_at' => $entry->publishedAt->timestamp,
            ],
            'links' => [
                'comments' => route('api.v1.changelogEntries.comments', ['entry' => $entry->id]),
                'web' => route('changelog.index'),
            ],
        ];
    }
}
