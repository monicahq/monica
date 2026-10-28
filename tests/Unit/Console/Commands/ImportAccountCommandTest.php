<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Enums\AccountImportStatusEnum;
use App\Enums\DefaultTemplateEnum;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\SeedLifeEventCategories;
use App\Jobs\SeedRelationshipTypes;
use App\Models\AccountImport;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportAccountCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_a_file_from_the_console_and_prints_a_summary(): void
    {
        Storage::fake('local');
        Notification::fake();

        $michael = User::factory()->create(['email' => 'michael@dundermifflin.test']);
        new SeedLifeEventCategories(user: $michael)->handle();
        new CreateDefaultTemplate(user: $michael)->handle();
        new CreateDefaultTemplate(user: $michael, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        new CreateDefaultLifeEventTypes(user: $michael)->handle();
        new SeedRelationshipTypes(user: $michael)->handle();

        $this->artisan('monica:import-account', [
            'file' => base_path('tests/Fixtures/Imports/monica-4-small.json'),
            '--email' => 'michael@dundermifflin.test',
            '--vault-name' => 'Dunder Mifflin',
        ])
            ->expectsOutputToContain('Found 5 contacts.')
            ->expectsOutputToContain('Writing contact items...')
            ->expectsOutputToContain('contact: wrote 5, left 0 out.')
            ->expectsOutputToContain('Imported')
            ->assertSuccessful();

        $import = AccountImport::query()->where('user_id', $michael->id)->firstOrFail();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->status);
        $this->assertTrue(Contact::query()->where('vault_id', $import->vault_id)->where('first_name', 'Dwight')->exists());
    }

    #[Test]
    public function it_refuses_an_address_nobody_uses(): void
    {
        $this->artisan('monica:import-account', [
            'file' => base_path('tests/Fixtures/Imports/monica-4-small.json'),
            '--email' => 'toby@dundermifflin.test',
        ])
            ->expectsOutputToContain('Nobody on this instance uses the address toby@dundermifflin.test.')
            ->assertFailed();
    }

    #[Test]
    public function it_says_why_a_file_was_refused(): void
    {
        Storage::fake('local');
        User::factory()->create(['email' => 'michael@dundermifflin.test']);

        $this->artisan('monica:import-account', [
            'file' => base_path('tests/Fixtures/Imports/monica-4-future.json'),
            '--email' => 'michael@dundermifflin.test',
        ])
            ->expectsOutputToContain('9.0')
            ->assertFailed();

        $this->assertSame(0, AccountImport::query()->count());
    }

    #[Test]
    public function it_discards_an_import_it_could_not_start_so_a_later_one_is_not_blocked(): void
    {
        Storage::fake('local');
        User::factory()->create(['email' => 'michael@dundermifflin.test']);

        $this->artisan('monica:import-account', [
            'file' => base_path('tests/Fixtures/Imports/monica-4-small.json'),
            '--email' => 'michael@dundermifflin.test',
            '--vault-name' => 'Dunder Mifflin',
            '--me' => '10000000-0000-4000-8000-000000000999',
        ])
            ->expectsOutputToContain('The contact chosen as you is not in this file.')
            ->assertFailed();

        $this->assertSame(0, AccountImport::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
