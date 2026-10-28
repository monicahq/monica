<?php

declare(strict_types=1);

namespace App\Enums;

use App\Actions\ReadMonicaFile;
use App\Actions\ReadMonicaFourFile;
use App\Contracts\SourceFormatVisitor;
use App\Contracts\Translatable;
use Override;

enum AccountImportSourceEnum: string implements Translatable
{
    case MonicaFour = 'monica_4';
    case Monica = 'monica';

    public static function fromVersion(mixed $version): ?self
    {
        return match ($version) {
            ReadMonicaFourFile::VERSION => self::MonicaFour,
            ReadMonicaFile::VERSION => self::Monica,
            default => null,
        };
    }

    public function accept(SourceFormatVisitor $visitor): void
    {
        match ($this) {
            self::Monica => $visitor->visitMonica(),
            self::MonicaFour => $visitor->visitMonicaFour(),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MonicaFour => 'Monica 4',
            self::Monica => 'Monica',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
