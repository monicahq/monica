<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\VaultColourEnum;
use App\Exceptions\PossessionTypeFileIsNotReadable;
use App\Helpers\PossessionTypeFileReader;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeFileReaderTest extends TestCase
{
    #[Test]
    public function it_reads_a_valid_file(): void
    {
        $raw = json_encode([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => [
                'name' => '  Office equipment  ',
                'colour' => VaultColourEnum::Teal->value,
                'icon' => 'briefcase',
                'groups' => [
                    [
                        'name' => 'Purchase',
                        'fields' => [
                            ['name' => 'Price', 'kind' => 'number'],
                        ],
                    ],
                ],
                'fields' => [
                    ['name' => 'Condition', 'kind' => 'select', 'options' => [' New ', 'Used']],
                    ['name' => 'Serial number', 'kind' => 'text', 'options' => ['ignored']],
                ],
            ],
        ]);

        $file = PossessionTypeFileReader::read($raw);

        $this->assertSame('Office equipment', $file['name']);
        $this->assertSame(VaultColourEnum::Teal->value, $file['colour']);
        $this->assertSame('briefcase', $file['icon']);
        $this->assertCount(1, $file['groups']);
        $this->assertSame('Purchase', $file['groups'][0]['name']);
        $this->assertSame(PossessionFieldKindEnum::Number, $file['groups'][0]['fields'][0]['kind']);
        $this->assertNull($file['groups'][0]['fields'][0]['options']);
        $this->assertSame(PossessionFieldKindEnum::Select, $file['fields'][0]['kind']);
        $this->assertSame(['New', 'Used'], $file['fields'][0]['options']);
        $this->assertNull($file['fields'][1]['options']);
    }

    #[Test]
    public function it_gives_the_slate_colour_when_the_file_names_none(): void
    {
        $raw = json_encode([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => ['name' => 'Vehicle'],
        ]);

        $file = PossessionTypeFileReader::read($raw);

        $this->assertSame(VaultColourEnum::Slate->value, $file['colour']);
        $this->assertNull($file['icon']);
        $this->assertSame([], $file['groups']);
        $this->assertSame([], $file['fields']);
    }

    #[Test]
    public function it_refuses_an_icon_outside_the_catalogue(): void
    {
        $raw = json_encode([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => ['name' => 'Vehicle', 'icon' => 'beet'],
        ]);

        try {
            PossessionTypeFileReader::read($raw);
            $this->fail('The file was read.');
        } catch (PossessionTypeFileIsNotReadable $exception) {
            $this->assertCount(1, $exception->problems);
        }
    }

    #[Test]
    public function it_refuses_an_empty_string(): void
    {
        $this->expectException(PossessionTypeFileIsNotReadable::class);

        PossessionTypeFileReader::read('   ');
    }

    #[Test]
    public function it_refuses_invalid_json(): void
    {
        try {
            PossessionTypeFileReader::read('{"format": ');
            $this->fail('The file was read.');
        } catch (PossessionTypeFileIsNotReadable $exception) {
            $this->assertCount(1, $exception->problems);
        }
    }

    #[Test]
    public function it_reports_every_problem_of_a_file_together(): void
    {
        $raw = json_encode([
            'format' => 'possession_type',
            'possession_type' => [
                'name' => 'Office equipment',
                'groups' => [
                    ['fields' => []],
                ],
                'fields' => [
                    ['name' => 'Power', 'kind' => 'wattage'],
                    ['name' => 'Condition', 'kind' => 'select', 'options' => []],
                ],
            ],
        ]);

        try {
            PossessionTypeFileReader::read($raw);
            $this->fail('The file was read.');
        } catch (PossessionTypeFileIsNotReadable $exception) {
            $this->assertGreaterThanOrEqual(3, count($exception->problems));
            $this->assertNotEmpty(array_filter(
                $exception->problems,
                fn (string $problem): bool => str_contains($problem, 'Group 1'),
            ));
        }
    }

    #[Test]
    public function it_refuses_a_choice_longer_than_a_field_accepts(): void
    {
        $raw = json_encode([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => [
                'name' => 'Office equipment',
                'fields' => [
                    ['name' => 'Branch', 'kind' => 'select', 'options' => ['Scranton', str_repeat('a', 256)]],
                ],
            ],
        ]);

        try {
            PossessionTypeFileReader::read($raw);
            $this->fail('The file was read.');
        } catch (PossessionTypeFileIsNotReadable $exception) {
            $this->assertCount(1, $exception->problems);
        }
    }

    #[Test]
    public function it_reads_a_choice_field_without_choices(): void
    {
        $raw = json_encode([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => [
                'name' => 'Office equipment',
                'fields' => [
                    ['name' => 'Branch', 'kind' => 'select', 'options' => []],
                ],
            ],
        ]);

        $read = PossessionTypeFileReader::read($raw);

        $this->assertSame(PossessionFieldKindEnum::Select, $read['fields'][0]['kind']);
        $this->assertSame([], $read['fields'][0]['options']);
    }

    #[Test]
    public function it_refuses_a_file_larger_than_the_limit(): void
    {
        $this->expectException(PossessionTypeFileIsNotReadable::class);

        PossessionTypeFileReader::read(str_repeat('a', PossessionTypeFileReader::MAX_BYTES + 1));
    }

    #[Test]
    public function it_refuses_a_choice_that_is_not_text(): void
    {
        $raw = json_encode([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => [
                'name' => 'Office equipment',
                'fields' => [
                    ['name' => 'Branch', 'kind' => 'select', 'options' => [['Scranton'], 'Stamford']],
                ],
            ],
        ]);

        try {
            PossessionTypeFileReader::read($raw);
            $this->fail('The file was read.');
        } catch (PossessionTypeFileIsNotReadable $exception) {
            $this->assertCount(1, $exception->problems);
        }
    }
}
