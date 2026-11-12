<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SupportQueue;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportQueueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_on_the_requests_to_handle_oldest_first(): void
    {
        SupportRequest::factory()->toHandle()->create(['subject' => 'Reminders not received', 'created_at' => now()->subHours(3)]);
        SupportRequest::factory()->toHandle()->create(['subject' => 'vCard import stops at 50', 'created_at' => now()->subDays(8)]);
        SupportRequest::factory()->waitingOnUser()->create(['subject' => 'Sort people by last contacted']);

        $queue = new SupportQueue(view: 'Scranton');

        $this->assertEquals(['vCard import stops at 50', 'Reminders not received'], $queue->query()->pluck('subject')->all());
        $this->assertEquals(['to_handle' => 2, 'waiting_on_user' => 1, 'solved' => 0, 'all' => 3], $queue->counts());
    }

    #[Test]
    public function it_filters_by_text_category_user_and_age(): void
    {
        $jonas = User::factory()->create(['first_name' => 'Jonas', 'last_name' => 'Weber']);
        $import = SupportRequest::factory()->create(['user_id' => $jonas->id, 'category' => 'technical', 'subject' => 'vCard import stops at 50', 'created_at' => now()->subDays(8)]);
        SupportMessage::factory()->create(['support_request_id' => $import->id, 'body' => 'It has a photo embedded as base64.']);
        SupportRequest::factory()->create(['category' => 'billing', 'subject' => 'October invoice', 'number' => 247020, 'created_at' => now()->subHours(19)]);

        $subjects = fn (SupportQueue $queue): array => $queue->query()->pluck('subject')->all();

        $this->assertEquals(['vCard import stops at 50'], $subjects(new SupportQueue(view: 'all', search: 'base64')));
        $this->assertEquals(['vCard import stops at 50'], $subjects(new SupportQueue(view: 'all', search: 'weber')));
        $this->assertEquals(['October invoice'], $subjects(new SupportQueue(view: 'all', search: 'MON-247020')));
        $this->assertEquals(['October invoice'], $subjects(new SupportQueue(view: 'all', category: 'billing')));
        $this->assertEquals(['vCard import stops at 50'], $subjects(new SupportQueue(view: 'all', user: (string) $jonas->id)));
        $this->assertEquals(['October invoice'], $subjects(new SupportQueue(view: 'all', age: 'day')));
        $this->assertEquals(['vCard import stops at 50'], $subjects(new SupportQueue(view: 'all', age: 'old')));
    }

    #[Test]
    public function it_ignores_a_filter_it_does_not_know(): void
    {
        $queue = new SupportQueue(category: 'paper', user: 'Dwight', age: 'forever');

        $this->assertFalse($queue->hasFilters());
        $this->assertEquals(['view' => 'to_handle'], $queue->parameters());
    }
}
