<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Transfer;

use App\Actions\ReadMonicaFourFile;
use App\Enums\AccountImportSourceEnum;
use App\Enums\AccountImportStatusEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\ReadAccountImport;
use App\Jobs\WriteAccountImportBatch;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $michael;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
        config(['monica.self_hosted' => true]);

        $this->michael = User::factory()->create();
        new CreateDefaultTemplate(user: $this->michael)->handle();
    }

    #[Test]
    public function it_shows_the_export_and_import_screen(): void
    {
        $this->actingAs($this->michael)->withConfirmedPassword()
            ->get(route('settings.accountImports.index'))
            ->assertOk()
            ->assertSee('Export and import')
            ->assertSee('Read this file');
    }

    #[Test]
    public function it_shows_the_screen_without_the_password_and_asks_for_it_in_a_card(): void
    {
        $this->actingAs($this->michael)
            ->get(route('settings.accountImports.index'))
            ->assertOk()
            ->assertSee('Confirm it is you');
    }

    #[Test]
    public function it_takes_a_file_and_has_it_read(): void
    {
        $response = $this->actingAs($this->michael)->withConfirmedPassword()->post(route('settings.accountImports.create'), [
            'file' => UploadedFile::fake()->createWithContent('monica.json', '{"version": "1.0-preview.1"}'),
        ]);

        $import = AccountImport::query()->where('user_id', $this->michael->id)->firstOrFail();

        $response->assertRedirect(route('settings.accountImports.show', $import));
        Queue::assertPushed(ReadAccountImport::class);
    }

    #[Test]
    public function it_refuses_an_upload_without_a_file(): void
    {
        $this->actingAs($this->michael)->withConfirmedPassword()
            ->post(route('settings.accountImports.create'))
            ->assertSessionHasErrors('file');
    }

    #[Test]
    public function it_shows_what_a_file_holds_and_asks_where_it_goes(): void
    {
        $import = $this->readImport();

        $this->actingAs($this->michael)->withConfirmedPassword()
            ->get(route('settings.accountImports.show', $import))
            ->assertOk()
            ->assertSee('What the file holds')
            ->assertSee('Dwight Schrute')
            ->assertSee('name="vault"', false)
            ->assertSee('name="me"', false);
    }

    #[Test]
    public function it_starts_writing_into_a_new_vault(): void
    {
        $import = $this->readImport();

        $this->actingAs($this->michael)->withConfirmedPassword()
            ->put(route('settings.accountImports.update', $import), [
                'vault_name' => 'Dunder Mifflin',
                'me' => '10000000-0000-4000-8000-000000000001',
            ])
            ->assertRedirect(route('settings.accountImports.show', $import))
            ->assertSessionHas('status');

        $this->assertSame(AccountImportStatusEnum::Writing, $import->refresh()->status);
        Queue::assertPushed(WriteAccountImportBatch::class);
    }

    #[Test]
    public function it_puts_a_refusal_from_the_limits_on_the_form(): void
    {
        config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 2, 'monica.free_vault_limit' => 5]);
        $import = $this->readImport();

        $this->actingAs($this->michael)->withConfirmedPassword()
            ->put(route('settings.accountImports.update', $import), ['vault_name' => 'Dunder Mifflin'])
            ->assertSessionHasErrors('vault');

        $this->assertSame(AccountImportStatusEnum::Read, $import->refresh()->status);
    }

    #[Test]
    public function it_cancels_an_import_and_goes_back_to_the_list(): void
    {
        $import = $this->readImport();

        $this->actingAs($this->michael)->withConfirmedPassword()
            ->delete(route('settings.accountImports.destroy', $import))
            ->assertRedirect(route('settings.accountImports.index'));

        $this->assertModelMissing($import);
    }

    #[Test]
    public function it_tells_the_reader_when_the_import_has_already_ended(): void
    {
        $import = $this->readImport();
        $import->update(['status' => AccountImportStatusEnum::Completed]);

        $this->actingAs($this->michael)->withConfirmedPassword()
            ->delete(route('settings.accountImports.destroy', $import))
            ->assertRedirect(route('settings.accountImports.show', $import))
            ->assertSessionHas('error', 'This import has already ended.');

        $this->assertModelExists($import);
    }

    private function readImport(): AccountImport
    {
        $import = AccountImport::factory()->create(['user_id' => $this->michael->id]);
        Storage::disk('local')->put($import->path, (string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        new ReadMonicaFourFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read, 'source_format' => AccountImportSourceEnum::MonicaFour]);

        return $import;
    }
}
