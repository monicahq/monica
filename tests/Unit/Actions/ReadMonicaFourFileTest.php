<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ReadMonicaFourFile;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\DatePrecisionEnum;
use App\Exceptions\ImportFileIsNotReadable;
use App\Models\AccountImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadMonicaFourFileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_every_kind_the_file_holds_into_items(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        $kinds = $import->items()->get()->countBy('kind')->all();

        $this->assertSame(5, $kinds['contact']);
        $this->assertSame(2, $kinds['file']);
        $this->assertSame(3, $kinds['reminder']);
        $this->assertSame(1, $kinds['life_event']);
        $this->assertSame(1, $kinds['call']);
        $this->assertSame(1, $kinds['conversation']);
        $this->assertSame(1, $kinds['pet']);
        $this->assertSame(1, $kinds['activity']);
        $this->assertSame(4, $kinds['relationship']);
        $this->assertSame(1, $kinds['relationship_type']);
        $this->assertSame(1, $kinds['life_event_type']);
        $this->assertSame(2, $kinds['journal_entry']);
        $this->assertSame(1, $kinds['archive']);
    }

    #[Test]
    public function it_writes_what_is_pointed_at_before_what_points_at_it(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        $order = $import->items()->pluck('kind')->unique()->values()->all();

        $this->assertLessThan(array_search('contact', $order, true), array_search('file', $order, true));
        $this->assertLessThan(array_search('relationship', $order, true), array_search('contact', $order, true));
        $this->assertSame('archive', end($order));
    }

    #[Test]
    public function it_offers_the_contact_the_file_marks_as_the_account_holder_as_me(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        $this->assertSame('10000000-0000-4000-8000-000000000001', $import->refresh()->me_source_uuid);
    }

    #[Test]
    public function it_leaves_out_the_reverse_of_a_relationship_already_read(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        $reverse = $import->items()->where('kind', 'relationship')->where('outcome', AccountImportOutcomeEnum::LeftOut)->get();

        $this->assertCount(2, $reverse);
        $this->assertSame(AccountImportReasonEnum::ReverseOfAnotherRelationship->value, $reverse->first()->reason);
        $this->assertSame(['name' => 'godfather'], $import->items()->where('kind', 'relationship_type')->value('payload'));
    }

    #[Test]
    public function it_decodes_the_embedded_files_into_the_folder_of_the_import(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        foreach ($import->items()->where('kind', 'file')->get() as $item) {
            $this->assertStringStartsWith($import->directory().'/files/', (string) $item->payload['path']);
            $this->assertStringNotContainsString((string) $item->source_uuid, (string) $item->payload['path']);
            Storage::disk('local')->assertExists((string) $item->payload['path']);
        }
    }

    #[Test]
    public function it_never_writes_a_file_outside_the_folder_of_the_import(): void
    {
        $import = $this->importHolding([
            'version' => ReadMonicaFourFile::VERSION,
            'account' => ['data' => [['type' => 'photo', 'values' => [[
                'uuid' => '../../../../escaped',
                'created_at' => '2005-03-24T09:00:00Z',
                'properties' => ['original_filename' => 'escaped.txt', 'dataUrl' => 'data:text/plain;base64,'.base64_encode('Creed was here')],
            ]]]]],
        ]);

        new ReadMonicaFourFile(import: $import)->execute();

        $item = $import->items()->where('kind', 'file')->sole();

        Storage::disk('local')->assertMissing('escaped');
        $this->assertSame([], collect(Storage::disk('local')->allFiles())->filter(fn (string $file): bool => Storage::disk('local')->get($file) === 'Creed was here')->all());
        $this->assertNull($item->source_uuid);
        $this->assertSame(AccountImportOutcomeEnum::LeftOut, $item->outcome);
    }

    #[Test]
    public function it_keeps_the_date_every_record_was_created_in_the_source(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        $items = $import->items()
            ->whereIn('kind', ['life_event', 'call', 'conversation', 'pet', 'activity', 'relationship', 'reminder', 'journal_entry', 'file'])
            ->whereNotNull('source_uuid')
            ->get();

        $this->assertSame(
            ['activity', 'call', 'conversation', 'file', 'journal_entry', 'life_event', 'pet', 'relationship', 'reminder'],
            $items->pluck('kind')->unique()->sort()->values()->all(),
        );

        foreach ($items as $item) {
            $this->assertIsString($item->payload['created_at'] ?? null, "The {$item->kind} {$item->source_uuid} lost its creation date.");
        }
    }

    #[Test]
    public function it_keeps_a_death_or_a_first_meeting_as_vague_as_the_file_has_it(): void
    {
        $import = $this->importHolding([
            'version' => ReadMonicaFourFile::VERSION,
            'account' => ['data' => [['type' => 'contact', 'values' => [[
                'uuid' => '10000000-0000-4000-8000-000000000009',
                'created_at' => '2005-03-24T09:00:00Z',
                'properties' => [
                    'first_name' => 'Creed',
                    'is_dead' => true,
                    'deceased_date' => ['is_age_based' => false, 'is_year_unknown' => true, 'date' => '1900-11-01T00:00:00Z', 'created_at' => '2010-01-01T00:00:00Z'],
                    'first_met_date' => ['is_age_based' => true, 'is_year_unknown' => false, 'date' => '1990-01-01T00:00:00Z'],
                ],
            ]]]]],
        ]);

        new ReadMonicaFourFile(import: $import)->execute();

        $lifeEvents = $import->items()->where('kind', 'life_event')->get()->keyBy('payload.nature');

        $this->assertSame(DatePrecisionEnum::Unknown->value, $lifeEvents['death']->payload['date_precision']);
        $this->assertSame('2010-01-01T00:00:00Z', $lifeEvents['death']->payload['created_at']);
        $this->assertSame(DatePrecisionEnum::Year->value, $lifeEvents['first_meeting']->payload['date_precision']);
    }

    #[Test]
    public function it_counts_what_the_file_holds_and_what_will_not_come_over(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        $summary = $import->refresh()->summary;

        $this->assertSame(['found' => 5, 'left_out' => 0], $summary['contacts']);
        $this->assertSame(['found' => 4, 'left_out' => 2], $summary['relationships']);
        $this->assertSame(['found' => 1, 'left_out' => 1], $summary['audit_logs']);
        $this->assertSame(['found' => 1, 'left_out' => 1], $summary['address_book_subscriptions']);
        $this->assertSame(['found' => 1, 'left_out' => 1], $summary['users']);
        $this->assertSame(['found' => 2, 'left_out' => 0], $summary['tags']);
        $this->assertSame(22, $import->items_total);
    }

    #[Test]
    public function it_keeps_what_the_file_says_its_identifiers_mean(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        $references = $import->refresh()->source_references;

        $this->assertSame('M', $references['genders']['40000000-0000-4000-8000-000000000001']);
        $this->assertSame('first_kiss', $references['life_event_types']['80000000-0000-4000-8000-000000000002']['key']);
    }

    #[Test]
    public function it_never_keeps_a_credential_the_file_carries(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();

        $kept = json_encode([
            $import->items()->pluck('payload')->all(),
            $import->refresh()->summary,
            $import->source_references,
        ]);

        $this->assertStringNotContainsString('JBSWY3DPEHPK3PXP', (string) $kept);
        $this->assertStringNotContainsString('dwight.schrute', (string) $kept);
        $this->assertStringNotContainsString('dav.schrutefarms.test', (string) $kept);
    }

    #[Test]
    public function it_reads_the_same_file_again_without_doubling_its_items(): void
    {
        $import = $this->importOf('monica-4-small.json');

        new ReadMonicaFourFile(import: $import)->execute();
        new ReadMonicaFourFile(import: $import)->execute();

        $this->assertSame(24, $import->items()->count());
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_complete(): void
    {
        $import = $this->importOf('monica-4-invalid.json');

        $this->expectException(ImportFileIsNotReadable::class);

        new ReadMonicaFourFile(import: $import)->execute();
    }

    #[Test]
    public function it_refuses_a_version_it_does_not_know(): void
    {
        $import = $this->importOf('monica-4-future.json');

        $this->expectException(ImportFileIsNotReadable::class);
        $this->expectExceptionMessage('9.0');

        new ReadMonicaFourFile(import: $import)->execute();
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_a_monica_four_export(): void
    {
        Storage::fake('local');
        $import = AccountImport::factory()->create();
        Storage::disk('local')->put($import->path, '{"version": "2.0-preview.1", "account": {}}');

        $this->expectException(ImportFileIsNotReadable::class);

        new ReadMonicaFourFile(import: $import)->execute();
    }

    #[Test]
    public function it_leaves_out_a_file_larger_than_this_instance_accepts(): void
    {
        config(['monica.max_file_size_in_kilobytes' => 1]);

        $import = $this->importHolding([
            'version' => ReadMonicaFourFile::VERSION,
            'account' => ['data' => [['type' => 'photo', 'values' => [[
                'uuid' => '30000000-0000-4000-8000-000000000001',
                'created_at' => '2005-03-24T09:00:00Z',
                'properties' => ['original_filename' => 'jello-stapler.jpg', 'dataUrl' => 'data:image/jpeg;base64,'.base64_encode(str_repeat('a', 4096))],
            ]]]]],
        ]);

        new ReadMonicaFourFile(import: $import)->execute();

        $item = $import->items()->where('kind', 'file')->sole();

        $this->assertSame(AccountImportOutcomeEnum::LeftOut, $item->outcome);
        $this->assertSame(AccountImportReasonEnum::FileTooLarge->value, $item->reason);
        $this->assertSame([$import->path], Storage::disk('local')->allFiles());
    }

    /** @param array<string, mixed> $contents */
    private function importHolding(array $contents): AccountImport
    {
        Storage::fake('local');

        $import = AccountImport::factory()->create();
        Storage::disk('local')->put($import->path, json_encode($contents, JSON_THROW_ON_ERROR));

        return $import;
    }

    private function importOf(string $fixture): AccountImport
    {
        Storage::fake('local');

        $import = AccountImport::factory()->create();
        Storage::disk('local')->put($import->path, (string) file_get_contents(base_path('tests/Fixtures/Imports/'.$fixture)));

        return $import;
    }
}
