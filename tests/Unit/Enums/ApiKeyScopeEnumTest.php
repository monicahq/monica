<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Contracts\Translatable;
use App\Enums\ApiKeyScopeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiKeyScopeEnumTest extends TestCase
{
    #[Test]
    public function it_names_the_scopes_a_key_can_carry(): void
    {
        $this->assertEquals(
            ['sensitive-fields', 'administration'],
            ApiKeyScopeEnum::values(),
        );
    }

    #[Test]
    public function it_is_translatable(): void
    {
        $scope = ApiKeyScopeEnum::SensitiveFields;

        $this->assertInstanceOf(Translatable::class, $scope);
        $this->assertEquals(
            [$scope->label(), $scope->description()],
            $scope->translationKeys(),
        );
    }
}
