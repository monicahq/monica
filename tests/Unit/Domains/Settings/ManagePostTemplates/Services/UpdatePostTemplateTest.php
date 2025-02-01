<?php

namespace Tests\Unit\Domains\Settings\ManagePostTemplates\Services;

use App\Domains\Settings\ManagePostTemplates\Services\UpdatePostTemplate;
use App\Models\Account;
use App\Models\PostTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePostTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_post_template(): void
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
        (new UpdatePostTemplate)->execute($request);
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

    /** @test */
    public function it_fails_if_post_template_doesnt_belong_to_template(): void
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
            'label' => 'Business',
        ];

        $postTemplate = (new UpdatePostTemplate)->execute($request);

        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate->id,
            'account_id' => $account->id,
            'label' => 'Business',
        ]);

        $this->assertInstanceOf(
            PostTemplate::class,
            $postTemplate
        );
    }
}
