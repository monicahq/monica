<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Support;

use App\Models\Administrator;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_screen_where_somebody_asks_for_help(): void
    {
        $this->instanceRunBy('David', 'Wallace');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('support.requests.new'));

        $response->assertStatus(200);
        $response->assertSee('What do you need help with?', escape: false);
        $response->assertSee('David', escape: false);
        $response->assertSee('name="category"', escape: false);
    }

    #[Test]
    public function it_offers_no_support_when_nobody_runs_the_instance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('support.requests.new'))->assertNotFound();
        $this->actingAs($user)->post(route('support.requests.create'), [])->assertNotFound();
    }

    #[Test]
    public function it_opens_a_request_and_shows_its_number(): void
    {
        Queue::fake();
        Notification::fake();
        Storage::fake();
        $this->instanceRunBy('David', 'Wallace');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('support.requests.create'), [
            'category' => 'billing',
            'subject' => 'October invoice shows the wrong company name',
            'body' => 'The invoice still says Dunder Mifflin Paper Company.',
            'files' => [UploadedFile::fake()->create('invoice-october.pdf', 88, 'application/pdf')],
        ]);

        $supportRequest = SupportRequest::query()->firstOrFail();

        $response->assertRedirect(route('support.requests.confirmation.show', $supportRequest));

        $this->actingAs($user)->get(route('support.requests.confirmation.show', $supportRequest))
            ->assertStatus(200)
            ->assertSee('Your request has been sent', escape: false)
            ->assertSee($supportRequest->reference, escape: false)
            ->assertSee('invoice-october.pdf', escape: false);
    }

    #[Test]
    public function it_refuses_a_request_too_short_to_start_from(): void
    {
        $this->instanceRunBy('David', 'Wallace');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('support.requests.create'), [
            'category' => 'question',
            'subject' => '',
            'body' => 'Help',
        ]);

        $response->assertSessionHasErrors(['subject', 'body']);
        $this->assertDatabaseCount('support_requests', 0);
    }

    #[Test]
    public function it_refuses_more_files_than_one_message_carries(): void
    {
        $this->instanceRunBy('David', 'Wallace');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('support.requests.create'), [
            'category' => 'technical',
            'subject' => 'Import keeps timing out',
            'body' => 'Every screen I got along the way is attached.',
            'files' => array_map(fn (int $index): UploadedFile => UploadedFile::fake()->image("step-{$index}.png"), range(1, 6)),
        ]);

        $response->assertSessionHasErrors(['files']);
    }

    #[Test]
    public function it_refuses_a_file_over_ten_megabytes(): void
    {
        $this->instanceRunBy('David', 'Wallace');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('support.requests.create'), [
            'category' => 'technical',
            'subject' => 'Import keeps timing out',
            'body' => 'The whole export is attached.',
            'files' => [UploadedFile::fake()->create('export.pdf', 10241, 'application/pdf')],
        ]);

        $response->assertSessionHasErrors(['files.0']);
    }

    #[Test]
    public function it_lists_the_requests_of_the_user(): void
    {
        $user = User::factory()->create();
        SupportRequest::factory()->create(['account_id' => $user->account_id, 'subject' => 'Birthday reminders arrive a day late']);

        $this->actingAs($user)->get(route('support.requests.index'))
            ->assertStatus(200)
            ->assertSee('Your requests', escape: false)
            ->assertSee('Birthday reminders arrive a day late', escape: false);
    }

    #[Test]
    public function it_shows_a_request(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create(['account_id' => $user->account_id, 'subject' => 'Birthday reminders arrive a day late']);
        SupportMessage::factory()->create(['support_request_id' => $supportRequest->id]);

        $this->actingAs($user)->get(route('support.requests.show', $supportRequest))
            ->assertStatus(200)
            ->assertSee('Birthday reminders arrive a day late', escape: false)
            ->assertSee('Mark as solved', escape: false);
    }

    #[Test]
    public function it_keeps_mark_as_solved_on_screen_and_says_why_it_is_refused(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->solved()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('support.requests.show', $supportRequest))
            ->assertSee('aria-disabled="true"', escape: false)
            ->assertSee('aria-describedby="solved-notice"', escape: false);
    }

    #[Test]
    public function it_offers_no_new_request_on_the_list_when_nobody_runs_the_instance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('support.requests.index'))
            ->assertStatus(200)
            ->assertDontSee(route('support.requests.new'), escape: false);
    }

    #[Test]
    public function it_points_the_subject_at_its_error_once_a_request_is_refused(): void
    {
        $this->instanceRunBy('David', 'Wallace');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('support.requests.new'))
            ->followingRedirects()
            ->post(route('support.requests.create'), ['category' => 'question', 'subject' => '', 'body' => 'Where did the October reminders go?'])
            ->assertSee("(true ? ' subject-error' : '')", escape: false);
    }

    private function instanceRunBy(string $firstName, string $lastName): User
    {
        $administrator = User::factory()->create(['first_name' => $firstName, 'last_name' => $lastName]);
        Administrator::factory()->create(['user_id' => $administrator->id]);

        return $administrator;
    }
}
