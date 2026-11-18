<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeCatalogueReloadControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_to_the_screen_with_the_catalogue_read_again(): void
    {
        Queue::fake();
        $this->fakeStripe();

        $this->actingAs($this->administrator())
            ->post(route('admin.billing.catalogueReload.create'))
            ->assertRedirect(route('admin.billing.index'))
            ->assertSessionHas('status', 'The catalogue was read again.');
    }

    #[Test]
    public function it_redirects_with_the_catalogue_could_not_be_read(): void
    {
        Queue::fake();
        $this->fakeStripe()->failOn('prices*');

        $this->actingAs($this->administrator())
            ->post(route('admin.billing.catalogueReload.create'))
            ->assertRedirect(route('admin.billing.index'))
            ->assertSessionHas('error', 'The catalogue could not be read.');
    }

    private function administrator(): User
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
