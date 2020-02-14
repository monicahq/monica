<?php

namespace Tests\Unit\Services\Instance\AuditLog;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Instance\AuditLog;
use Illuminate\Validation\ValidationException;
use App\Services\Instance\AuditLog\LogAccountAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LogAccountActionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_logs_an_action(): void
    {
        $michael = factory(User::class)->create([]);

        $date = Carbon::now();

        $request = [
            'account_id' => $michael->account_id,
            'action' => 'account_created',
            'author_id' => $michael->id,
            'author_name' => $michael->name,
            'audited_at' => $date,
            'objects' => '{"user": 1}',
        ];

        $auditLog = (new LogAccountAction)->execute($request);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'account_id' => $michael->account_id,
            'about_contact_id' => null,
            'action' => 'account_created',
            'author_id' => $michael->id,
            'author_name' => $michael->name,
            'audited_at' => $date,
            'should_appear_on_dashboard' => false,
            'objects' => '{"user": 1}',
        ]);

        $this->assertInstanceOf(
            AuditLog::class,
            $auditLog
        );
    }

    /** @test */
    public function it_logs_an_action_about_a_contact(): void
    {
        $michael = factory(User::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $michael->account_id,
        ]);

        $date = Carbon::now();

        $request = [
            'account_id' => $michael->account_id,
            'action' => 'account_created',
            'about_contact_id' => $contact->id,
            'author_id' => $michael->id,
            'author_name' => $michael->name,
            'audited_at' => $date,
            'objects' => '{"user": 1}',
        ];

        $auditLog = (new LogAccountAction)->execute($request);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'account_id' => $michael->account_id,
            'about_contact_id' => $contact->id,
            'action' => 'account_created',
            'author_id' => $michael->id,
            'author_name' => $michael->name,
            'audited_at' => $date,
            'should_appear_on_dashboard' => false,
            'objects' => '{"user": 1}',
        ]);

        $this->assertInstanceOf(
            AuditLog::class,
            $auditLog
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'action' => 'account_created',
        ];

        $this->expectException(ValidationException::class);
        (new LogAccountAction)->execute($request);
    }
}
