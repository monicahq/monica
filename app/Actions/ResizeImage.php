<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use InvalidArgumentException;
use RuntimeException;

/**
 * Resize an uploaded image to a box and write it to disk as WebP.
 */
class ResizeImage
{
    private const int QUALITY = 80;

    public function __construct(
        private readonly UploadedFile $file,
        private readonly int $width,
        private readonly int $height,
        private readonly string $path,
        private readonly string $name,
        private readonly string $disk,
    ) {}

    public function execute(): string
    {
        $this->validate();

        return $this->resize();
    }

    private function validate(): void
    {
        if ($this->width < 1 || $this->height < 1) {
            throw new InvalidArgumentException('The width and height must be positive');
        }
    }

    private function resize(): string
    {
        $path = Image::fromUpload($this->file)
            ->orient()
            ->cover($this->width, $this->height)
            ->toWebp()
            ->quality(self::QUALITY)
            ->storeAs(path: $this->path, name: $this->name, disk: $this->disk);

        if ($path === false) {
            throw new RuntimeException('The image could not be stored');
        }

        return $path;
    }
}
