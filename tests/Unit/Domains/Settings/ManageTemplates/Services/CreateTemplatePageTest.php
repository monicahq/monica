<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\CreateTemplatePage;
use App\Models\Account;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateTemplatePageTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_template_page(): void
    {
        $ross = $this->createAdministrator();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $template);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateTemplatePage)->execute($request);
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
        $this->executeService($ross, $account, $template);
    }

    /** @test */
    public function it_fails_if_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $template = Template::factory()->create();
        $this->executeService($ross, $ross->account, $template);
    }

    private function executeService(User $author, Account $account, Template $template): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'template_id' => $template->id,
            'name' => 'Business awesome',
            'can_be_deleted' => true,
        ];

        $templatePage = (new CreateTemplatePage)->execute($request);

        $this->assertDatabaseHas('template_pages', [
            'id' => $templatePage->id,
            'template_id' => $template->id,
            'name' => 'Business awesome',
            'slug' => 'business-awesome',
            'can_be_deleted' => true,
        ]);

        $this->assertInstanceOf(
            TemplatePage::class,
            $templatePage
        );
    }
}
