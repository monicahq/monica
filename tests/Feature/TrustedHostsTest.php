<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustedHostsTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    #[Test]
    public function it_answers_on_every_host_when_no_host_is_trusted(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://monica.example.com', 'app.trusted_hosts' => []]);

        $this->get('https://dundermifflin.example.org/up')->assertOk();
    }

    #[Test]
    public function it_refuses_a_host_that_is_not_trusted(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://monica.example.com', 'app.trusted_hosts' => ['^contacts\.example\.org$']]);

        $this->get('https://dundermifflin.example.org/up')->assertStatus(400);
    }

    #[Test]
    public function it_answers_on_the_hosts_it_trusts_and_on_the_host_of_the_application(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://monica.example.com', 'app.trusted_hosts' => ['^contacts\.example\.org$']]);

        $this->get('https://contacts.example.org/up')->assertOk();
        $this->get('https://monica.example.com/up')->assertOk();
        $this->get('https://scranton.monica.example.com/up')->assertOk();
    }
}
