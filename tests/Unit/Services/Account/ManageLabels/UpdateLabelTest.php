<?php

namespace Tests\Unit\Services\Account\ManageLabels;

use Tests\TestCase;
use App\Models\User;
use App\Models\Label;
use App\Models\Account;
use App\Jobs\CreateAuditLog;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NotEnoughPermissionException;
use App\Services\Account\ManageLabels\UpdateLabel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateLabelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_label(): void
    {
        $ross = $this->createAdministrator();
        $label = Label::factory()->create([
            'account_id' => $ross->account->id,
        ]);
        $this->executeService($ross, $ross->account, $label);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateLabel)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $label = Label::factory()->create([
            'account_id' => $ross->account->id,
        ]);
        $this->executeService($ross, $account, $label);
    }

    /** @test */
    public function it_fails_if_label_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $label = Label::factory()->create();
        $this->executeService($ross, $ross->account, $label);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $label = Label::factory()->create([
            'account_id' => $ross->account->id,
        ]);
        $this->executeService($ross, $ross->account, $label);
    }

    private function executeService(User $author, Account $account, Label $label): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'label_id' => $label->id,
            'name' => 'label name',
        ];

        $label = (new UpdateLabel)->execute($request);

        $this->assertDatabaseHas('labels', [
            'id' => $label->id,
            'account_id' => $account->id,
            'name' => 'label name',
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'label_updated';
        });
    }
}
