<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\WallpaperHelper;

class WallpaperHelperTest extends TestCase
{
    /** @test */
    public function it_returns_a_random_wallpaper(): void
    {
        $this->assertStringStartsWith(
            env('APP_URL').'/',
            WallpaperHelper::getRandomWallpaper()
        );
    }
}
