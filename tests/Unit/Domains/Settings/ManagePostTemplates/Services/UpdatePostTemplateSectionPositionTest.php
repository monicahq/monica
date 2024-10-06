<?php

namespace Tests\Unit\Domains\Settings\ManagePostTemplates\Services;

use App\Domains\Settings\ManagePostTemplates\Services\UpdatePostTemplateSectionPosition;
use App\Models\Account;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePostTemplateSectionPositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_post_template_section_position(): void
    {
        $ross = $this->createAdministrator();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $postTemplateSection = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 2,
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
        (new UpdatePostTemplateSectionPosition)->execute($request);
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
        $postTemplateSection = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
        ]);
        $this->executeService($ross, $account, $postTemplate, $postTemplateSection);
    }

    /** @test */
    public function it_fails_if_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $postTemplate = PostTemplate::factory()->create();
        $postTemplateSection = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
        ]);
        $this->executeService($ross, $ross->account, $postTemplate, $postTemplateSection);
    }

    /** @test */
    public function it_fails_if_template_page_doesnt_belong_to_template(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $postTemplateSection = PostTemplateSection::factory()->create();
        $this->executeService($ross, $ross->account, $postTemplate, $postTemplateSection);
    }

    private function executeService(User $author, Account $account, PostTemplate $postTemplate, PostTemplateSection $postTemplateSection): void
    {
        $postTemplateSection1 = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 1,
        ]);
        $postTemplateSection3 = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 3,
        ]);
        $postTemplateSection4 = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'post_template_id' => $postTemplate->id,
            'post_template_section_id' => $postTemplateSection->id,
            'new_position' => 3,
        ];

        $postTemplateSection = (new UpdatePostTemplateSectionPosition)->execute($request);

        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection1->id,
            'post_template_id' => $postTemplate->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection3->id,
            'post_template_id' => $postTemplate->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection4->id,
            'post_template_id' => $postTemplate->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection->id,
            'post_template_id' => $postTemplate->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $postTemplateSection = (new UpdatePostTemplateSectionPosition)->execute($request);

        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection1->id,
            'post_template_id' => $postTemplate->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection3->id,
            'post_template_id' => $postTemplate->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection4->id,
            'post_template_id' => $postTemplate->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'id' => $postTemplateSection->id,
            'post_template_id' => $postTemplate->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            PostTemplateSection::class,
            $postTemplateSection
        );
    }
}
