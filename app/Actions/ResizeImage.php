<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use InvalidArgumentException;
use RuntimeException;

/**
 * Resize an uploaded image to a box and write it to disk as WebP. The result
 * fills the box rather than fitting inside it, so nothing is left off centre
 * when the image is shown in a circle, and the parts that fall outside are
 * cropped away.
 *
 * Anything that needs a picture at a given size goes through here, which is why
 * the action knows nothing about employees or avatars.
 */
class ResizeImage
{
    /**
     * WebP holds up well at 80, and the file stays small enough that two
     * versions of it cost less than one of the original.
     */
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

    /**
     * orient() first, because a photo taken on a phone carries its rotation in
     * its EXIF data rather than in its pixels, and cropping it before turning
     * it upright would cut the wrong part away.
     */
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
