<?php

namespace Tests\Unit\Helpers;

use App\Helpers\WallpaperHelper;
use Tests\TestCase;

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
