<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\FieldCompletion;
use App\Helpers\RecordKind;
use App\Models\FieldValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class FieldCompletionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_the_records_holding_each_field_against_those_offered_it(): void
    {
        $fixture = AnimalsVault::build();

        $completion = $this->completion($fixture, version: null, order: FieldCompletion::FORM_ORDER);

        $this->assertSame(14, $completion['records']);
        $this->assertSame(['Identification', 'Care'], array_column($completion['sections'], 'name'));
        $this->assertSame([
            ['key' => 'birth_date', 'label' => 'Birth date', 'filled' => 12, 'offeredTo' => 14, 'percentage' => 86, 'availableFrom' => null],
            ['key' => 'microchip', 'label' => 'Microchip', 'filled' => 5, 'offeredTo' => 11, 'percentage' => 45, 'availableFrom' => 3],
        ], $completion['sections'][0]['fields']);
        $this->assertSame([8, 14, 57], [
            $completion['sections'][1]['fields'][0]['filled'],
            $completion['sections'][1]['fields'][0]['offeredTo'],
            $completion['sections'][1]['fields'][0]['percentage'],
        ]);
    }

    #[Test]
    public function it_puts_the_lowest_completion_first_within_a_section(): void
    {
        $fixture = AnimalsVault::build();

        $completion = $this->completion($fixture, version: null, order: FieldCompletion::LOWEST_FIRST);

        $this->assertSame(['microchip', 'birth_date'], array_column($completion['sections'][0]['fields'], 'key'));
    }

    #[Test]
    public function it_leaves_a_set_aside_value_out_and_shows_zero_for_a_field_nobody_filled(): void
    {
        $fixture = AnimalsVault::build();
        FieldValue::query()->where('field_id', $fixture->fields['microchip']->id)->update(['is_detached' => true]);

        $completion = $this->completion($fixture, version: 3, order: FieldCompletion::FORM_ORDER);

        $this->assertSame(11, $completion['records']);
        $this->assertSame([0, 11, 0], [
            $completion['sections'][0]['fields'][1]['filled'],
            $completion['sections'][0]['fields'][1]['offeredTo'],
            $completion['sections'][0]['fields'][1]['percentage'],
        ]);
    }

    #[Test]
    public function it_counts_a_sensitive_field_without_naming_it(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->fields['microchip']->updateQuietly(['is_sensitive' => true]);

        $hidden = $this->completion($fixture, version: null, order: FieldCompletion::FORM_ORDER, namesSensitiveFields: false);
        $named = $this->completion($fixture, version: null, order: FieldCompletion::FORM_ORDER, namesSensitiveFields: true);

        $this->assertSame(['birth_date'], array_column($hidden['sections'][0]['fields'], 'key'));
        $this->assertSame(1, $hidden['hiddenCount']);
        $this->assertSame(['birth_date', 'microchip'], array_column($named['sections'][0]['fields'], 'key'));
    }

    #[Test]
    public function it_answers_nothing_for_a_template_no_record_uses(): void
    {
        $fixture = AnimalsVault::build();

        $completion = new FieldCompletion(
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            template: $fixture->bird,
            versionNumber: null,
            order: FieldCompletion::FORM_ORDER,
            namesSensitiveFields: true,
        )->toArray();

        $this->assertSame(['records' => 0, 'sections' => [], 'hiddenCount' => 0], $completion);
    }

    /** @return array<string, mixed> */
    private function completion(AnimalsVault $fixture, ?int $version, string $order, bool $namesSensitiveFields = true): array
    {
        return new FieldCompletion(
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            template: $fixture->pet,
            versionNumber: $version,
            order: $order,
            namesSensitiveFields: $namesSensitiveFields,
        )->toArray();
    }
}
