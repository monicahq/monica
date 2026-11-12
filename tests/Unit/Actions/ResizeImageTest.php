<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ResizeImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResizeImageTest extends TestCase
{
    #[Test]
    public function it_writes_a_webp_at_the_size_it_was_asked_for(): void
    {
        Storage::fake('local');

        $path = new ResizeImage(
            file: UploadedFile::fake()->image('dwight.jpg', 400, 400),
            width: 96,
            height: 96,
            path: 'photos/1',
            name: 'beets.webp',
            disk: 'local',
        )->execute();

        $this->assertEquals('photos/1/beets.webp', $path);

        Storage::disk('local')->assertExists($path);

        $size = getimagesizefromstring((string) Storage::disk('local')->get($path));

        $this->assertEquals(96, $size[0]);
        $this->assertEquals(96, $size[1]);
        $this->assertEquals('image/webp', $size['mime']);
    }

    #[Test]
    public function it_crops_a_rectangle_to_the_box_rather_than_squashing_it(): void
    {
        Storage::fake('local');

        $path = new ResizeImage(
            file: UploadedFile::fake()->image('michael.jpg', 800, 200),
            width: 96,
            height: 96,
            path: 'photos/1',
            name: 'boss.webp',
            disk: 'local',
        )->execute();

        $size = getimagesizefromstring((string) Storage::disk('local')->get($path));

        $this->assertEquals(96, $size[0]);
        $this->assertEquals(96, $size[1]);
    }

    /** @return array<string, array{0: int, 1: int}> */
    public static function dimensionsThatAreNotPositive(): array
    {
        return [
            'a width of zero' => [0, 96],
            'a negative height' => [96, -10],
        ];
    }

    #[Test]
    #[DataProvider('dimensionsThatAreNotPositive')]
    public function it_refuses_a_dimension_that_is_not_positive(int $width, int $height): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ResizeImage(
            file: UploadedFile::fake()->image('pam.jpg'),
            width: $width,
            height: $height,
            path: 'photos/1',
            name: 'art.webp',
            disk: 'local',
        )->execute();
    }
}
