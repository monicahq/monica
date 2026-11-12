<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<SupportAttachment> */
class SupportAttachmentFactory extends Factory
{
    protected $model = SupportAttachment::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'support_message_id' => SupportMessage::factory(),
            'account_id' => fn (array $attributes): int => SupportMessage::query()->with('supportRequest')->findOrFail($attributes['support_message_id'])->supportRequest->account_id,
            'disk' => 'local',
            'path' => fn (array $attributes): string => SupportAttachment::directoryFor($attributes['account_id']).'/'.Str::uuid()->toString(),
            'original_name' => 'screenshot.png',
            'mime_type' => 'image/png',
            'size_in_bytes' => 245760,
        ];
    }
}
