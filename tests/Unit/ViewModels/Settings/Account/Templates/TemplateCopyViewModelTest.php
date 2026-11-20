<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Models\Template;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\TemplateCopyViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateCopyViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_suggests_copy_of_the_original_name(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin contact']);

        $values = new TemplateCopyViewModel(user: $user, template: $template)->values();

        $this->assertEquals('Copy of Dunder Mifflin contact', $values['name']);
    }

    #[Test]
    public function it_suggests_the_slug_the_copy_would_get(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin contact']);

        $values = new TemplateCopyViewModel(user: $user, template: $template)->values();

        $this->assertEquals('copy-of-dunder-mifflin-contact', $values['slug']);
    }

    #[Test]
    public function it_skips_a_slug_already_taken(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin contact']);
        Template::factory()->create(['account_id' => $user->account_id, 'slug' => 'copy-of-dunder-mifflin-contact']);

        $values = new TemplateCopyViewModel(user: $user, template: $template)->values();

        $this->assertEquals('copy-of-dunder-mifflin-contact-2', $values['slug']);
    }

    #[Test]
    public function it_skips_a_slug_held_by_a_deleted_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin contact']);
        Template::factory()->create(['account_id' => $user->account_id, 'slug' => 'copy-of-dunder-mifflin-contact'])->delete();

        $values = new TemplateCopyViewModel(user: $user, template: $template)->values();

        $this->assertEquals('copy-of-dunder-mifflin-contact-2', $values['slug']);
    }

    #[Test]
    public function it_ignores_a_slug_taken_in_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin contact']);
        Template::factory()->create(['slug' => 'copy-of-dunder-mifflin-contact']);

        $values = new TemplateCopyViewModel(user: $user, template: $template)->values();

        $this->assertEquals('copy-of-dunder-mifflin-contact', $values['slug']);
    }

    #[Test]
    public function it_marks_a_suggested_slug_as_untouched(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin contact']);

        $values = new TemplateCopyViewModel(user: $user, template: $template)->values();

        $this->assertFalse($values['slugTouched']);
    }
}
