<?php

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Queue;
use App\Jobs\AuditLog\LogAccountAudit;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
    public function it_validates_rules(): void
    {
        $rules = [
            'street' => 'nullable|string|max:255',
        ];

        $stub = $this->getMockForAbstractClass(BaseService::class);
        $stub->rules([$rules]);

        $this->assertTrue(
            $stub->validate([
                'street' => 'la rue du bonheur',
            ])
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
            $stub->nullOrValue($array, 'value')
        );

        $array = [
            'otherValue' => '',
        ];

        $this->assertNull(
            $stub->nullOrValue($array, 'otherValue')
        );

        $array = [];

        $this->assertNull(
            $stub->nullOrValue($array, 'value')
        );
    }

    /** @test */
    public function it_returns_null_or_the_actual_date(): void
    {
        $stub = $this->getMockForAbstractClass(BaseService::class);
        $array = [
            'value' => '1990-01-01',
        ];

        $this->assertInstanceOf(
            Carbon::class,
            $stub->nullOrDate($array, 'value')
        );

        $array = [
            'otherValue' => '',
        ];

        $this->assertNull(
            $stub->nullOrDate($array, 'otherValue')
        );

        $array = [];

        $this->assertNull(
            $stub->nullOrDate($array, 'value')
        );
    }

    /** @test */
    public function it_writes_an_audit_log_for_the_action(): void
    {
        Queue::fake();

        $stub = $this->getMockForAbstractClass(BaseService::class);

        $michael = factory(User::class)->create([]);
        $action = 'account_created';
        $objects = [
            'id' => 3,
        ];

        $stub->writeAuditLog($michael, $action, $objects);

        Queue::assertPushed(LogAccountAudit::class, function ($job) use ($michael, $action) {
            return $job->auditLog['action'] === $action &&
                $job->auditLog['author_id'] === $michael->id &&
                $job->auditLog['objects'] === json_encode([
                    'id' => 3,
                ]);
        });
    }
}
