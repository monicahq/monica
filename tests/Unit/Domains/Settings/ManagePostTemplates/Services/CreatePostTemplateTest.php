<?php

namespace Tests\Unit\Domains\Settings\ManagePostTemplates\Services;

use App\Domains\Settings\ManagePostTemplates\Services\CreatePostTemplate;
use App\Models\Account;
use App\Models\PostTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreatePostTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_post_template(): void
    {
        $ross = $this->createAdministrator();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreatePostTemplate)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    private function executeService(User $author, Account $account): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'label' => 'Business',
            'can_be_deleted' => false,
        ];

        $postTemplate = (new CreatePostTemplate)->execute($request);

        $this->assertDatabaseHas('post_templates', [
            'id' => $postTemplate->id,
            'account_id' => $account->id,
            'label' => 'Business',
            'position' => 1,
        ]);

        $this->assertInstanceOf(
            PostTemplate::class,
            $postTemplate
        );
    }
}
