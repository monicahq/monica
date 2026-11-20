<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplatePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_template_nothing_renders_while_another_contact_template_remains(): void
    {
        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->published()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $template));
    }

    #[Test]
    public function it_refuses_deleting_a_template_records_still_render_with(): void
    {
        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->used(9)->create(['structure_id' => $template->structure->id]);

        $response = Gate::forUser($user)->inspect('delete', $template);

        $this->assertSame('The template still renders records and stays.', $response->message());
    }

    #[Test]
    public function it_refuses_deleting_the_last_template_for_contacts(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->published()->create(['account_id' => $user->account_id]);

        $response = Gate::forUser($user)->inspect('delete', $template);

        $this->assertSame('This is the last template for contacts, and a new vault needs one, so it stays.', $response->message());
    }

    #[Test]
    public function it_allows_deleting_the_last_template_for_an_animal(): void
    {
        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->forEntity()->published()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $template));
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->published()->create(['account_id' => $user->account_id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $template));
    }
}
