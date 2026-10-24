<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\McpPayload;
use App\Models\Account;
use App\Models\Field;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpPayloadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_masks_a_key_named_like_a_secret_wherever_it_sits(): void
    {
        $masked = McpPayload::mask([
            'vault' => 'dunder-mifflin',
            'password' => 'bears-beets-battlestar',
            'options' => ['api_key' => 'sk_live_1', 'Authorization' => 'Bearer x', 'note' => 'kept'],
            'people' => [['name' => 'Dwight', 'access_token' => 'abc']],
            'client_secret' => 'shh',
        ], Account::factory()->create(), 'get-contact');

        $this->assertSame([
            'vault' => 'dunder-mifflin',
            'password' => McpPayload::MASK,
            'options' => ['api_key' => McpPayload::MASK, 'Authorization' => McpPayload::MASK, 'note' => 'kept'],
            'people' => [['name' => 'Dwight', 'access_token' => McpPayload::MASK]],
            'client_secret' => McpPayload::MASK,
        ], $masked);
    }

    #[Test]
    public function it_keeps_a_custom_value_only_when_its_field_is_one_of_the_account_and_not_sensitive(): void
    {
        $sensitive = Field::factory()->create(['is_sensitive' => true]);
        $plain = Field::factory()->create(['section_version_id' => $sensitive->section_version_id, 'is_sensitive' => false]);
        $elsewhere = Field::factory()->create(['is_sensitive' => false]);

        $masked = McpPayload::mask([
            'fields' => [
                $sensitive->id => 'Allergic to nothing but Toby',
                $plain->id => 'Beet farmer',
                $elsewhere->id => 'From another account',
                999999 => 'Unknown field',
            ],
        ], $sensitive->account, 'update-life-event');

        $this->assertSame([
            $sensitive->id => McpPayload::MASK,
            $plain->id => 'Beet farmer',
            $elsewhere->id => McpPayload::MASK,
            999999 => McpPayload::MASK,
        ], $masked['fields']);
    }

    #[Test]
    public function it_leaves_the_fields_of_a_tool_that_takes_no_custom_values_alone(): void
    {
        $fields = [['label' => 'Allergies', 'type' => 'text']];

        $masked = McpPayload::mask(['fields' => $fields], Account::factory()->create(), 'replace-life-event-type-fields');

        $this->assertSame($fields, $masked['fields']);
    }

    #[Test]
    public function it_keeps_a_payload_that_fits_whole(): void
    {
        $this->assertSame(
            ['text' => '{"name":"Pam Beesly","url":"https://dundermifflin.com"}', 'truncated' => false],
            McpPayload::encode(['name' => 'Pam Beesly', 'url' => 'https://dundermifflin.com']),
        );
    }

    #[Test]
    public function it_cuts_a_payload_that_is_too_long_without_splitting_a_character(): void
    {
        $encoded = McpPayload::encode(['narrative' => str_repeat('é', McpPayload::MAXIMUM_BYTES)]);

        $this->assertTrue($encoded['truncated']);
        $this->assertLessThanOrEqual(McpPayload::MAXIMUM_BYTES, strlen($encoded['text']));
        $this->assertTrue(mb_check_encoding($encoded['text'], 'UTF-8'));
        $this->assertStringStartsWith('{"narrative":"éé', $encoded['text']);
    }
}
