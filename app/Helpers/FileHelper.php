<?php

namespace App\Helpers;

class FileHelper
{
    /**
     * Formats the file size to a human readable size.
     */
    public static function formatFileSize(int $bytes): ?string
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $step = 1024;
        $i = 0;
        while (($bytes / $step) > 0.9) {
            $bytes = $bytes / $step;
            $i++;
        }

        return round($bytes, 2).$units[$i];
    }
}
