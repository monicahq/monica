<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BreadcrumbVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_always_starts_at_the_vault(): void
    {
        $vault = Vault::factory()->create(['name' => 'Dunder Mifflin']);

        $this->blade('<x-breadcrumb.vault :vault="$vault" />', ['vault' => $vault])
            ->assertSee('Dunder Mifflin')
            ->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function the_vault_becomes_a_link_once_something_follows_it(): void
    {
        $vault = Vault::factory()->create(['name' => 'Dunder Mifflin']);

        $this->blade(
            '<x-breadcrumb.vault :vault="$vault" :items="[[\'label\' => \'People\']]" />',
            ['vault' => $vault],
        )
            ->assertSee(route('vaults.show', $vault), escape: false)
            ->assertSee('Dunder Mifflin')
            ->assertSee('People');
    }

    #[Test]
    public function it_keeps_the_order_it_was_given(): void
    {
        $vault = Vault::factory()->create(['name' => 'Dunder Mifflin']);

        $this->blade(
            '<x-breadcrumb.vault :vault="$vault" :items="[[\'label\' => \'People\', \'url\' => \'/p\'], [\'label\' => \'Michael\']]" />',
            ['vault' => $vault],
        )->assertSeeInOrder(['Dunder Mifflin', 'People', 'Michael']);
    }
}
