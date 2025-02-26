<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'journal_id',
        'slice_of_life_id',
        'title',
        'view_count',
        'published',
        'written_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published' => 'boolean',
        'written_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the journal associated with the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the slice of life associated with the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SliceOfLife, $this>
     */
    public function sliceOfLife(): BelongsTo
    {
        return $this->belongsTo(SliceOfLife::class);
    }

    /**
     * Get the post sections associated with the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PostSection, $this>
     */
    public function postSections(): HasMany
    {
        return $this->hasMany(PostSection::class);
    }

    /**
     * Get the contacts associated with the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * Get the post's feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\ContactFeedItem, $this>
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }

    /**
     * Get the tags associated with the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Get the files associated with the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\File, $this>
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get the post metrics associated with the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PostMetric, $this>
     */
    public function postMetrics(): HasMany
    {
        return $this->hasMany(PostMetric::class);
    }

    /**
     * Get the post's title.
     *
     * @return Attribute<string,string>
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value) {
                    return $value;
                }

                return trans('Undefined');
            },
            set: fn ($value) => $value,
        );
    }

    /**
     * Get the post's body excerpt limited to 200 characters.
     * Considers mentions to be 20 character strings and will not stop inside a mention token
     *
     * @return Attribute<string,never>
     */
    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: function () {
                $content = optional($this->postSections()->whereNotNull('content')->first())->content;

                if (! $content) {
                    return '';
                }

                $maxLength = 200; // Target character limit
                $actualLength = 0; // Tracks the actual length considering tokens
                $safeCutoff = strlen($content); // The last safe position to cut
                $adjustedContent = ''; // Stores the truncated content

                // Match all mention tokens
                preg_match_all('/\{\{\{CONTACT-ID:[a-f0-9\-]+\|[^}]+\}\}\}/', $content, $matches, PREG_OFFSET_CAPTURE);
                $tokenPositions = $matches[0]; // List of tokens and their positions

                // Iterate through the content while respecting the maxLength
                $index = 0;
                while ($actualLength < $maxLength && $index < strlen($content)) {
                    $isToken = false;

                    // Check if current position is the start of a token
                    foreach ($tokenPositions as $match) {
                        $tokenText = $match[0];
                        $tokenStart = $match[1];
                        $tokenEnd = $tokenStart + strlen($tokenText);

                        if ($index === $tokenStart) {
                            // If adding this token exceeds max length, stop
                            if ($actualLength + 20 > $maxLength) {
                                break 2;
                            }

                            // Append the full token and count it as 20 characters
                            $adjustedContent .= $tokenText;
                            $actualLength += 20;
                            $index = $tokenEnd; // Skip past the entire token
                            $isToken = true;
                            break;
                        }
                    }

                    // If it's not a token, process character-by-character
                    if (! $isToken) {
                        $adjustedContent .= $content[$index];
                        $actualLength++;
                        $index++;
                    }

                    // Always track the last safe position before a token
                    if (! $isToken) {
                        $safeCutoff = $index;
                    }
                }

                // Ensure we cut off safely before a token
                $adjustedContent = substr($adjustedContent, 0, $safeCutoff);

                return rtrim($adjustedContent).'...'; // Append ellipsis
            }
        );
    }
}
