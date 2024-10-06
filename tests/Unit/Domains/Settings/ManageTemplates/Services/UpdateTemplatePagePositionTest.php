<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\UpdateTemplatePagePosition;
use App\Models\Account;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateTemplatePagePositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_template_page_position(): void
    {
        $ross = $this->createAdministrator();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
            'position' => 2,
        ]);
        $this->executeService($ross, $ross->account, $template, $templatePage);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateTemplatePagePosition)->execute($request);
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
        $this->executeService($ross, $account, $template, $templatePage);
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
        $this->executeService($ross, $ross->account, $template, $templatePage);
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
        $this->executeService($ross, $ross->account, $template, $templatePage);
    }

    private function executeService(User $author, Account $account, Template $template, TemplatePage $templatePage): void
    {
        Queue::fake();

        $templatePage1 = TemplatePage::factory()->create([
            'template_id' => $template->id,
            'position' => 1,
        ]);
        $templatePage3 = TemplatePage::factory()->create([
            'template_id' => $template->id,
            'position' => 3,
        ]);
        $templatePage4 = TemplatePage::factory()->create([
            'template_id' => $template->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'template_id' => $template->id,
            'template_page_id' => $templatePage->id,
            'new_position' => 3,
        ];

        $templatePage = (new UpdateTemplatePagePosition)->execute($request);

        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage1->id,
            'template_id' => $template->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage3->id,
            'template_id' => $template->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage4->id,
            'template_id' => $template->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage->id,
            'template_id' => $template->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $templatePage = (new UpdateTemplatePagePosition)->execute($request);

        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage1->id,
            'template_id' => $template->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage3->id,
            'template_id' => $template->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage4->id,
            'template_id' => $template->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage->id,
            'template_id' => $template->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            TemplatePage::class,
            $templatePage
        );
    }
}
