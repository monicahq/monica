<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\UpdateTemplatePage;
use App\Models\Account;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateTemplatePageTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_template_page(): void
    {
        $ross = $this->createAdministrator();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
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
        (new UpdateTemplatePage)->execute($request);
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

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'template_id' => $template->id,
            'template_page_id' => $templatePage->id,
            'name' => 'Business',
        ];

        $templatePage = (new UpdateTemplatePage)->execute($request);

        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage->id,
            'template_id' => $template->id,
            'name' => 'Business',
            'slug' => 'business',
        ]);

        $this->assertInstanceOf(
            TemplatePage::class,
            $templatePage
        );
    }
}
