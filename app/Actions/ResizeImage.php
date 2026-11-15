<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use InvalidArgumentException;
use RuntimeException;

/**
 * Resize an uploaded image to a box and write it to disk as WebP. An image wider or taller than
 * MAX_SIDE_IN_PIXELS is refused before it is decoded, since decoding allocates every pixel.
 */
class ResizeImage
{
    private const int MAX_SIDE_IN_PIXELS = 8192;

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

        $size = @getimagesize((string) $this->file->getRealPath());

        if ($size === false) {
            throw new InvalidArgumentException('The file is not an image');
        }

        if ($size[0] > self::MAX_SIDE_IN_PIXELS || $size[1] > self::MAX_SIDE_IN_PIXELS) {
            throw new InvalidArgumentException('The image must not be wider or taller than 8192 pixels');
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
