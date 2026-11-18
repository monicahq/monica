<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddScheduledStripePriceToSubscriptionsTableTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_the_column_to_a_database_created_before_it_existed(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn('scheduled_stripe_price');
        });

        $this->migration()->up();

        $this->assertTrue(Schema::hasColumn('subscriptions', 'scheduled_stripe_price'));
    }

    #[Test]
    public function it_leaves_a_database_that_already_has_the_column_alone(): void
    {
        $this->migration()->up();

        $this->assertTrue(Schema::hasColumn('subscriptions', 'scheduled_stripe_price'));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_17_000004_add_scheduled_stripe_price_to_subscriptions_table.php');
    }
}
