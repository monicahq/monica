<?php

namespace Tests\Unit\Domains\Settings\ManagePostTemplates\Services;

use App\Domains\Settings\ManagePostTemplates\Services\CreatePostTemplateSection;
use App\Models\Account;
use App\Models\PostTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreatePostTemplateSectionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_post_template_section(): void
    {
        $ross = $this->createAdministrator();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $postTemplate);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreatePostTemplateSection)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $postTemplate);
    }

    /** @test */
    public function it_fails_if_post_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $postTemplate = PostTemplate::factory()->create();
        $this->executeService($ross, $ross->account, $postTemplate);
    }

    private function executeService(User $author, Account $account, PostTemplate $postTemplate): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'post_template_id' => $postTemplate->id,
            'label' => 'Business awesome',
            'can_be_deleted' => true,
        ];

        $postTemplateSection = (new CreatePostTemplateSection)->execute($request);

        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection->id,
            'post_template_id' => $postTemplate->id,
            'label' => 'Business awesome',
        ]);

        $this->assertInstanceOf(
            PostTemplate::class,
            $postTemplate
        );
    }
}
