<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\CheckDatabaseIsReady;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CheckDatabaseIsReadyTest extends TestCase
{
    #[Test]
    public function the_health_route_answers_while_the_database_does(): void
    {
        $this->get('/up')->assertOk();
    }

    #[Test]
    public function it_says_which_of_the_two_is_not_answering(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => '/nowhere/monica.sqlite',
        ]);
        DB::purge('sqlite');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The database is not answering.');

        new CheckDatabaseIsReady()->handle(new DiagnosingHealth);
    }
}
