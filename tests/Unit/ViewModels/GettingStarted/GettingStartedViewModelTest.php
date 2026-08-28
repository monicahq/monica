<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\GettingStarted;

use App\Models\Contact;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\GettingStarted\GettingStartedViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GettingStartedViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_greets_somebody_by_their_first_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $this->assertEquals(
            'Hello Michael, welcome to '.config('app.name').'.',
            new GettingStartedViewModel(user: $user)->greeting(),
        );
    }

    #[Test]
    public function it_lists_the_five_things_people_usually_do_first(): void
    {
        $user = User::factory()->create();

        $steps = new GettingStartedViewModel(user: $user)->steps();

        $this->assertCount(5, $steps);
        $this->assertEquals('Create your first vault', $steps[0]['title']);
        $this->assertEquals(route('vaults.new'), $steps[0]['url']);
        $this->assertEmpty(array_filter($steps, fn (array $step): bool => $step['done']));
        $this->assertEquals('0 of 5 done', new GettingStartedViewModel(user: $user)->progressLabel());
    }

    #[Test]
    public function it_points_the_step_about_adding_somebody_nowhere_while_there_is_no_screen_for_it(): void
    {
        $user = User::factory()->create();

        $steps = new GettingStartedViewModel(user: $user)->steps();

        $this->assertNull($steps[1]['url']);
        $this->assertNull($steps[1]['action']);
    }

    #[Test]
    public function it_reads_what_the_account_already_holds(): void
    {
        $user = User::factory()->create(['photo_path' => 'photos/michael/uuid']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        Template::factory()->create(['account_id' => $user->account_id, 'is_system' => false]);

        $viewModel = new GettingStartedViewModel(user: $user);
        $done = array_column($viewModel->steps(), 'done');

        $this->assertEquals([true, true, true, true, false], $done);
        $this->assertEquals('4 of 5 done', $viewModel->progressLabel());
    }

    #[Test]
    public function it_leaves_the_template_step_undone_while_the_account_only_has_the_one_it_was_given(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'is_system' => true]);

        $steps = new GettingStartedViewModel(user: $user)->steps();

        $this->assertFalse($steps[2]['done']);
    }

    #[Test]
    public function it_reads_nothing_from_another_account(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create();
        Template::factory()->create(['is_system' => false]);

        $this->assertEquals('0 of 5 done', new GettingStartedViewModel(user: $user)->progressLabel());
    }

    #[Test]
    public function it_says_the_letter_in_three_paragraphs(): void
    {
        $user = User::factory()->create();

        $this->assertCount(3, new GettingStartedViewModel(user: $user)->letter());
    }

    #[Test]
    public function it_signs_the_letter_with_both_names_and_a_circle_each(): void
    {
        $user = User::factory()->create();
        $viewModel = new GettingStartedViewModel(user: $user);

        $this->assertEquals('Regis and Alexis', $viewModel->founderName());
        $this->assertEquals('The two of us build Monica', $viewModel->founderRole());

        $portraits = $viewModel->portraits();

        $this->assertCount(2, $portraits);
        $this->assertEquals('Regis', $portraits[0]['name']);
        $this->assertEquals('Alexis', $portraits[1]['name']);
        $this->assertStringEndsWith('images/alexis.jpg', $portraits[1]['url']);
    }
}
