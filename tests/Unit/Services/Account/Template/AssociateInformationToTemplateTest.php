<?php

namespace Tests\Unit\Services\Account\Template;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Template;
use App\Models\Information;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Template\AssociateInformationToTemplate;

class AssociateInformationToTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_associates_an_information_to_a_template(): void
    {
        $ross = $this->createAdministrator();
        $information = Information::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $information, $template);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Male',
        ];

        $this->expectException(ValidationException::class);
        (new AssociateInformationToTemplate)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $information = Information::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $information, $template);
    }

    /** @test */
    public function it_fails_if_information_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $information = Information::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $information, $template);
    }

    /** @test */
    public function it_fails_if_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $information = Information::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $template = Template::factory()->create([]);
        $this->executeService($ross, $ross->account, $information, $template);
    }

    private function executeService(User $author, Account $account, Information $information, Template $template): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'information_id' => $information->id,
            'template_id' => $template->id,
            'position' => 3,
        ];

        $template = (new AssociateInformationToTemplate)->execute($request);

        $this->assertDatabaseHas('information_template', [
            'information_id' => $information->id,
            'template_id' => $template->id,
            'position' => 3,
        ]);

        $this->assertInstanceOf(
            Template::class,
            $template
        );
    }
}
