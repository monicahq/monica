<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\AssociateModuleToTemplatePage;
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

class AssociateModuleToTemplatePageTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_associates_a_module_to_a_template_page(): void
    {
        $ross = $this->createAdministrator();
        $module = Module::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $this->executeService($ross, $ross->account, $module, $template, $templatePage);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Male',
        ];

        $this->expectException(ValidationException::class);
        (new AssociateModuleToTemplatePage)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $module = Module::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $this->executeService($ross, $account, $module, $template, $templatePage);
    }

    /** @test */
    public function it_fails_if_information_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $module = Module::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $this->executeService($ross, $ross->account, $module, $template, $templatePage);
    }

    /** @test */
    public function it_fails_if_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $module = Module::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $template = Template::factory()->create([]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $this->executeService($ross, $ross->account, $module, $template, $templatePage);
    }

    /** @test */
    public function it_fails_if_template_page_doesnt_belong_to_template(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $module = Module::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create();
        $this->executeService($ross, $ross->account, $module, $template, $templatePage);
    }

    private function executeService(User $author, Account $account, Module $module, Template $template, TemplatePage $templatePage): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'module_id' => $module->id,
            'template_id' => $template->id,
            'template_page_id' => $templatePage->id,
        ];

        $module = (new AssociateModuleToTemplatePage)->execute($request);

        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module->id,
            'template_page_id' => $templatePage->id,
            'position' => 1,
        ]);

        $this->assertInstanceOf(
            Module::class,
            $module
        );
    }
}
