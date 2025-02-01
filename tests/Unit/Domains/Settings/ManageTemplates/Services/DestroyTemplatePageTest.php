<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\DestroyTemplatePage;
use App\Models\Account;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyTemplatePageTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_template(): void
    {
        $ross = $this->createAdministrator();
        $templatePage = $this->createTemplatePage($ross->account);
        $this->executeService($ross, $ross->account, $templatePage);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyTemplatePage)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $templatePage = $this->createTemplatePage($ross->account);
        $this->executeService($ross, $account, $templatePage);
    }

    /** @test */
    public function it_fails_if_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $templatePage = $this->createTemplatePage($account);
        $this->executeService($ross, $ross->account, $templatePage);
    }

    private function executeService(User $author, Account $account, TemplatePage $templatePage): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'template_id' => $templatePage->template_id,
            'template_page_id' => $templatePage->id,
        ];

        (new DestroyTemplatePage)->execute($request);

        $this->assertDatabaseMissing('template_pages', [
            'id' => $templatePage->id,
        ]);
    }

    private function createTemplatePage(Account $account): TemplatePage
    {
        $template = Template::factory()->create([
            'account_id' => $account->id,
        ]);

        return TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
    }
}
