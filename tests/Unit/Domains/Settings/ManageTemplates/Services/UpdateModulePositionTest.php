<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\UpdateModulePosition;
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

class UpdateModulePositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_module_position(): void
    {
        $ross = $this->createAdministrator();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $module = Module::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage->modules()->syncWithoutDetaching([$module->id => ['position' => 2]]);
        $this->executeService($ross, $ross->account, $template, $templatePage, $module);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateModulePosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $module = Module::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage->modules()->syncWithoutDetaching([$module->id => ['position' => 2]]);
        $this->executeService($ross, $account, $template, $templatePage, $module);
    }

    /** @test */
    public function it_fails_if_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $template = Template::factory()->create();
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $module = Module::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage->modules()->syncWithoutDetaching([$module->id => ['position' => 2]]);
        $this->executeService($ross, $ross->account, $template, $templatePage, $module);
    }

    /** @test */
    public function it_fails_if_template_page_doesnt_belong_to_template(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create();
        $module = Module::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage->modules()->syncWithoutDetaching([$module->id => ['position' => 2]]);
        $this->executeService($ross, $ross->account, $template, $templatePage, $module);
    }

    /** @test */
    public function it_fails_if_module_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create();
        $module = Module::factory()->create();
        $this->executeService($ross, $ross->account, $template, $templatePage, $module);
    }

    private function executeService(User $author, Account $account, Template $template, TemplatePage $templatePage, Module $module): void
    {
        Queue::fake();

        $module1 = Module::factory()->create([
            'account_id' => $account->id,
        ]);
        $templatePage->modules()->syncWithoutDetaching([$module1->id => ['position' => 1]]);
        $module3 = Module::factory()->create([
            'account_id' => $account->id,
        ]);
        $templatePage->modules()->syncWithoutDetaching([$module3->id => ['position' => 3]]);
        $module4 = Module::factory()->create([
            'account_id' => $account->id,
        ]);
        $templatePage->modules()->syncWithoutDetaching([$module4->id => ['position' => 4]]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'template_id' => $template->id,
            'template_page_id' => $templatePage->id,
            'module_id' => $module->id,
            'new_position' => 3,
        ];

        $module = (new UpdateModulePosition)->execute($request);

        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module1->id,
            'template_page_id' => $templatePage->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module3->id,
            'template_page_id' => $templatePage->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module4->id,
            'template_page_id' => $templatePage->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module->id,
            'template_page_id' => $templatePage->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $module = (new UpdateModulePosition)->execute($request);

        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module1->id,
            'template_page_id' => $templatePage->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module3->id,
            'template_page_id' => $templatePage->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module4->id,
            'template_page_id' => $templatePage->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('module_template_page', [
            'module_id' => $module->id,
            'template_page_id' => $templatePage->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            Module::class,
            $module
        );
    }
}
