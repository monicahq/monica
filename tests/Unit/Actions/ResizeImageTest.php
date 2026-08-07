<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ResizeImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
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

    #[Test]
    public function it_refuses_a_width_that_is_not_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ResizeImage(
            file: UploadedFile::fake()->image('pam.jpg'),
            width: 0,
            height: 96,
            path: 'photos/1',
            name: 'art.webp',
            disk: 'local',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_height_that_is_not_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ResizeImage(
            file: UploadedFile::fake()->image('jim.jpg'),
            width: 96,
            height: -10,
            path: 'photos/1',
            name: 'prank.webp',
            disk: 'local',
        )->execute();
    }
}
