<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Transfer;

use App\Enums\AccountExportStatusEnum;
use App\Jobs\BuildAccountExport;
use App\Models\AccountExport;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $pam;

    private Vault $scranton;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->pam = User::factory()->create();
        $this->scranton = Vault::factory()->create(['account_id' => $this->pam->account_id, 'name' => 'Scranton']);
        Vault::factory()->create(['account_id' => $this->pam->account_id, 'name' => 'Nashua']);
    }

    #[Test]
    public function it_shows_the_export_form_with_its_warning(): void
    {
        $this->actingAs($this->pam)->withConfirmedPassword()
            ->get(route('settings.accountImports.index'))
            ->assertOk()
            ->assertSee('Prepare the export')
            ->assertSee('sensitive fields included')
            ->assertSee('name="vault"', false);
    }

    #[Test]
    public function it_asks_for_an_export_of_the_vault_chosen_and_goes_back_to_the_screen(): void
    {
        $this->actingAs($this->pam)->withConfirmedPassword()
            ->post(route('settings.accountExports.create'), ['vault' => $this->scranton->id])
            ->assertRedirect(route('settings.accountImports.index'))
            ->assertSessionHas('status');

        $this->assertSame([$this->scranton->id], AccountExport::query()->where('user_id', $this->pam->id)->firstOrFail()->vault_ids);
        Queue::assertPushed(BuildAccountExport::class);
    }

    #[Test]
    public function it_refuses_a_vault_that_is_not_a_number(): void
    {
        $this->actingAs($this->pam)->withConfirmedPassword()
            ->post(route('settings.accountExports.create'), ['vault' => 'scranton'])
            ->assertSessionHasErrors('vault');

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_says_why_when_an_export_is_already_being_prepared(): void
    {
        AccountExport::factory()->create(['user_id' => $this->pam->id, 'status' => AccountExportStatusEnum::Preparing]);

        $this->actingAs($this->pam)->withConfirmedPassword()
            ->post(route('settings.accountExports.create'), ['vault' => ''])
            ->assertRedirect(route('settings.accountImports.index'))
            ->assertSessionHas('error', 'An export is already being prepared.');
    }
}
