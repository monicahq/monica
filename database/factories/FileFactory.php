<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        $name = fake()->unique()->slug(2).'.pdf';

        return [
            'account_id' => Account::factory(),
            'vault_id' => null,
            'disk' => 'local',
            'path' => fn (array $attributes): string => File::directoryFor((int) $attributes['account_id'], null).'/'.Str::uuid()->toString(),
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size_in_bytes' => 2048,
            'checksum' => hash('sha256', $name),
            'width' => null,
            'height' => null,
            'variants' => null,
            'reference_count' => 0,
            'orphaned_at' => now(),
            'uploaded_by_user_id' => null,
        ];
    }

    /**
     * Indicate that something points at the file, which is what keeps the
     * sweep away from it.
     */
    public function used(int $times = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'reference_count' => $times,
            'orphaned_at' => null,
        ]);
    }

    /**
     * Indicate that the file is an image, which is what carries dimensions.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes): array => [
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'width' => 1200,
            'height' => 800,
        ]);
    }
}
