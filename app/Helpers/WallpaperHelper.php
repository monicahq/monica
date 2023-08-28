<?php

namespace App\Helpers;

use function Safe\shuffle;

class WallpaperHelper
{
    /**
     * Get a random wallpaper for the login page.
     *
     * The images come from Unsplash.com, and are all MIT licensed.
     */
    public static function getRandomWallpaper(): string
    {
        $photos = [
            'image1.png',
            'image2.png',
            'image3.png',
            'image4.png',
            'image5.png',
            'image6.png',
            'image7.png',
            'image8.png',
            'image9.png',
            'image10.png',
            'image11.png',
            'image12.png',
            'image13.png',
            'image14.png',
            'image15.png',
            'image16.png',
            'image17.png',
            'image18.png',
            'image19.png',
            'image20.png',
        ];

        shuffle($photos);

        return asset('img/'.$photos[0]);
    }
}
