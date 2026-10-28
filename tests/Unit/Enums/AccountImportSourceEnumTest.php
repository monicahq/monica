<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AccountImportSourceEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportSourceEnumTest extends TestCase
{
    #[Test]
    public function it_knows_the_format_each_version_of_the_file_belongs_to(): void
    {
        $this->assertSame(AccountImportSourceEnum::MonicaFour, AccountImportSourceEnum::fromVersion('1.0-preview.1'));
        $this->assertSame(AccountImportSourceEnum::Monica, AccountImportSourceEnum::fromVersion('2.0-preview.1'));
    }

    #[Test]
    public function it_knows_no_format_for_a_version_it_cannot_read(): void
    {
        $this->assertNull(AccountImportSourceEnum::fromVersion('3.0'));
        $this->assertNull(AccountImportSourceEnum::fromVersion(null));
    }
}
