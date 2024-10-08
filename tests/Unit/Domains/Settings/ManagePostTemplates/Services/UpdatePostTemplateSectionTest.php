<?php

namespace Tests\Unit\Domains\Settings\ManagePostTemplates\Services;

use App\Domains\Settings\ManagePostTemplates\Services\UpdatePostTemplateSection;
use App\Models\Account;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePostTemplateSectionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_post_template_section(): void
    {
        $ross = $this->createAdministrator();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $postTemplateSection = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
        ]);
        $this->executeService($ross, $ross->account, $postTemplate, $postTemplateSection);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdatePostTemplateSection)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $account->id,
        ]);
        $postTemplateSection = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
        ]);
        $this->executeService($ross, $account, $postTemplate, $postTemplateSection);
    }

    /** @test */
    public function it_fails_if_post_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $postTemplate = PostTemplate::factory()->create();
        $postTemplateSection = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
        ]);
        $this->executeService($ross, $ross->account, $postTemplate, $postTemplateSection);
    }

    /** @test */
    public function it_fails_if_post_template_section_doesnt_belong_to_post_template(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $account->id,
        ]);
        $postTemplateSection = PostTemplateSection::factory()->create();
        $this->executeService($ross, $ross->account, $postTemplate, $postTemplateSection);
    }

    private function executeService(User $author, Account $account, PostTemplate $postTemplate, PostTemplateSection $postTemplateSection): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'post_template_id' => $postTemplate->id,
            'post_template_section_id' => $postTemplateSection->id,
            'label' => 'name',
        ];

        (new UpdatePostTemplateSection)->execute($request);

        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection->id,
            'post_template_id' => $postTemplate->id,
            'label' => 'name',
        ]);

        $this->assertInstanceOf(
            PostTemplateSection::class,
            $postTemplateSection
        );
    }
}
