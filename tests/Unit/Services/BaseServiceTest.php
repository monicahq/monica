<?php

namespace Tests\Unit\Services;

use App\Services\BaseService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BaseServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_an_empty_rule_array(): void
    {
        $stub = $this->getMockForAbstractClass(BaseService::class);

        $this->assertIsArray(
            $stub->rules()
        );
    }

    /** @test */
    public function it_returns_an_empty_permission_array(): void
    {
        $stub = $this->getMockForAbstractClass(BaseService::class);

        $this->assertIsArray(
            $stub->permissions()
        );
    }

    /** @test */
    public function it_validates_rules(): void
    {
        $rules = [
            'line_1' => 'nullable|string|max:255',
        ];

        $stub = $this->getMockForAbstractClass(BaseService::class);
        $stub->rules();

        $this->assertTrue(
            $stub->validateRules([
                'line_1' => 'la rue du bonheur',
            ])
        );
    }

    /** @test */
    public function it_returns_the_default_value_or_the_given_value(): void
    {
        $stub = $this->getMockForAbstractClass(BaseService::class);
        $array = [
            'value' => true,
        ];

        $this->assertTrue(
            $stub->valueOrFalse($array, 'value')
        );

        $array = [
            'value' => false,
        ];

        $this->assertFalse(
            $stub->valueOrFalse($array, 'value')
        );
    }

    /** @test */
    public function it_returns_null_or_the_actual_value(): void
    {
        $stub = $this->getMockForAbstractClass(BaseService::class);
        $array = [
            'value' => 'this',
        ];

        $this->assertEquals(
            'this',
            $stub->valueOrNull($array, 'value')
        );

        $array = [
            'otherValue' => '',
        ];

        $this->assertNull(
            $stub->valueOrNull($array, 'otherValue')
        );

        $array = [];

        $this->assertNull(
            $stub->valueOrNull($array, 'value')
        );
    }
}
