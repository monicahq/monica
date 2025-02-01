<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\RemoveModuleFromTemplatePage;
use App\Models\Account;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveModuleFromTemplatePageTest extends TestCase
{
    use DatabaseTransactions;

    private Template $template;

    private TemplatePage $templatePage;

    private Module $module;

    /** @test */
    public function it_removes_a_module_from_a_template_page(): void
    {
        $ross = $this->createAdministrator();
        $this->associateTemplatePageAndModule($ross->account, $ross->account);
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Male',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveModuleFromTemplatePage)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->associateTemplatePageAndModule($account, $account);
        $this->executeService($ross, $account);
    }

    /** @test */
    public function it_fails_if_information_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->associateTemplatePageAndModule($ross->account, $account);
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->associateTemplatePageAndModule($account, $ross->account);
        $this->executeService($ross, $ross->account);
    }

    private function executeService(User $author, Account $account): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $this->templatePage->id,
            'module_id' => $this->module->id,
        ];

        (new RemoveModuleFromTemplatePage)->execute($request);

        $this->assertDatabaseMissing('module_template_page', [
            'module_id' => $this->module->id,
            'template_page_id' => $this->templatePage->id,
        ]);
    }

    private function associateTemplatePageAndModule(Account $accountA, Account $accountB): void
    {
        $this->template = Template::factory()->create([
            'account_id' => $accountA->id,
        ]);
        $this->templatePage = TemplatePage::factory()->create([
            'template_id' => $this->template->id,
        ]);
        $this->module = Module::factory()->create([
            'account_id' => $accountB->id,
        ]);

        $this->templatePage->modules()->syncWithoutDetaching([
            $this->module->id => ['position' => 1],
        ]);
    }
}
