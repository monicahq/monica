<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTransactionDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_attaches_a_document_to_a_transaction(): void
    {
        Queue::fake();
        Storage::fake('local');

        [$user, $vault, $sebring, $purchase] = $this->sebringPurchase();

        $this->actingAs($user)->post(route('possessions.transactions.documents.create', [$vault, $sebring, $purchase]), [
            'document' => UploadedFile::fake()->create('invoice-4021.pdf', 4, 'application/pdf'),
        ])
            ->assertRedirect(route('possessions.transactions.index', [$vault, $sebring]))
            ->assertSessionHas('status');

        $this->assertSame(['invoice-4021.pdf'], $purchase->documents()->pluck('original_name')->all());
    }

    #[Test]
    public function it_attaches_the_document_sent_with_a_new_transaction(): void
    {
        Queue::fake();
        Storage::fake('local');

        [$user, $vault, $sebring] = $this->sebringPurchase();

        $this->actingAs($user)->post(route('possessions.transactions.create', [$vault, $sebring]), [
            'type' => 'fee',
            'document' => UploadedFile::fake()->create('registration.pdf', 4, 'application/pdf'),
        ])->assertRedirect(route('possessions.transactions.index', [$vault, $sebring]));

        $fee = PossessionTransaction::query()->where('possession_id', $sebring->id)->where('type', 'fee')->sole();
        $this->assertSame(['registration.pdf'], $fee->documents()->pluck('original_name')->all());
    }

    #[Test]
    public function it_refuses_an_attachment_without_a_file(): void
    {
        [$user, $vault, $sebring, $purchase] = $this->sebringPurchase();

        $this->actingAs($user)->post(route('possessions.transactions.documents.create', [$vault, $sebring, $purchase]), [])
            ->assertSessionHasErrors('document');
    }

    #[Test]
    public function it_removes_a_document_from_a_transaction(): void
    {
        Queue::fake();

        [$user, $vault, $sebring, $purchase] = $this->sebringPurchase();
        $invoice = File::factory()->used(1)->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $purchase->documents()->attach($invoice);

        $this->actingAs($user)->delete(route('possessions.transactions.documents.destroy', [$vault, $sebring, $purchase, $invoice]))
            ->assertRedirect(route('possessions.transactions.index', [$vault, $sebring]))
            ->assertSessionHas('status');

        $this->assertSame(0, $purchase->documents()->count());
    }

    #[Test]
    public function a_transaction_reached_through_another_possession_is_not_found(): void
    {
        Storage::fake('local');

        [$user, $vault, , $purchase] = $this->sebringPurchase();
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.transactions.documents.create', [$vault, $mug, $purchase]), [
            'document' => UploadedFile::fake()->create('invoice-4021.pdf', 4),
        ])->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_when_files_are_not_offered(): void
    {
        Storage::fake('local');

        [$user, $vault, $sebring, $purchase] = $this->sebringPurchase();
        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->actingAs($user)->post(route('possessions.transactions.documents.create', [$vault, $sebring, $purchase]), [
            'document' => UploadedFile::fake()->create('invoice-4021.pdf', 4),
        ])->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Possession, 3: PossessionTransaction} */
    private function sebringPurchase(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);

        return [$user, $vault, $sebring, PossessionTransaction::factory()->create(['possession_id' => $sebring->id])];
    }
}
