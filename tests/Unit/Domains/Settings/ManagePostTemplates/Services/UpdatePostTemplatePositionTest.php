<?php

namespace Tests\Unit\Domains\Settings\ManagePostTemplates\Services;

use App\Domains\Settings\ManagePostTemplates\Services\UpdatePostTemplatePosition;
use App\Models\Account;
use App\Models\PostTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePostTemplatePositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_post_template_position(): void
    {
        $ross = $this->createAdministrator();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $ross->account_id,
            'position' => 2,
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
        (new UpdatePostTemplatePosition)->execute($request);
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
        $postTemplate1 = PostTemplate::factory()->create([
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $postTemplate3 = PostTemplate::factory()->create([
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $postTemplate4 = PostTemplate::factory()->create([
            'account_id' => $account->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'post_template_id' => $postTemplate->id,
            'new_position' => 3,
        ];

        $postTemplate = (new UpdatePostTemplatePosition)->execute($request);

        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate3->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $postTemplate = (new UpdatePostTemplatePosition)->execute($request);

        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate3->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            PostTemplate::class,
            $postTemplate
        );
    }
}
