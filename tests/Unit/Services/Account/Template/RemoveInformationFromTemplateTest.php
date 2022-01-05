<?php

namespace Tests\Unit\Services\Account\Template;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Template;
use App\Models\Information;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\ManageTemplate\RemoveInformationFromTemplate;

class RemoveInformationFromTemplateTest extends TestCase
{
    use DatabaseTransactions;

    private Template $template;
    private Information $information;

    /** @test */
    public function it_removes_an_information_from_a_template(): void
    {
        $ross = $this->createAdministrator();
        $this->associateTemplateAndInformation($ross->account, $ross->account);
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Male',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveInformationFromTemplate)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->associateTemplateAndInformation($account, $account);
        $this->executeService($ross, $account);
    }

    /** @test */
    public function it_fails_if_information_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->associateTemplateAndInformation($ross->account, $account);
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->associateTemplateAndInformation($account, $ross->account);
        $this->executeService($ross, $ross->account);
    }

    private function executeService(User $author, Account $account): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'information_id' => $this->information->id,
            'template_id' => $this->template->id,
        ];

        (new RemoveInformationFromTemplate)->execute($request);

        $this->assertDatabaseMissing('information_template', [
            'information_id' => $this->information->id,
            'template_id' => $this->template->id,
        ]);

        $information = $this->information;
        $template = $this->template;
    }

    private function associateTemplateAndInformation(Account $accountA, Account $accountB): void
    {
        $this->template = Template::factory()->create([
            'account_id' => $accountA->id,
        ]);
        $this->information = Information::factory()->create([
            'account_id' => $accountB->id,
        ]);

        $this->template->informations()->syncWithoutDetaching([
            $this->information->id => ['position' => 1],
        ]);
    }
}
