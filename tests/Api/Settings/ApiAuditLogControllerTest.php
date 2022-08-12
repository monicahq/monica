<?php

namespace Tests\Api\Settings;

use Tests\ApiTestCase;
use App\Models\Instance\AuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiAuditLogControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureAuditLog = [
        'id',
        'object',
        'author' => [
            'name',
        ],
        'action',
        'objects',
        'audited_at',
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_gets_a_list_of_audit_logs()
    {
        $user = $this->signin();

        factory(AuditLog::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureAuditLog],
        ]);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);
    }

    /** @test */
    public function it_is_possible_to_get_audit_logs_and_limit_query_and_paginate()
    {
        $user = $this->signin();

        factory(AuditLog::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/logs?limit=1&page=2');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureAuditLog],
        ]);

        $response->assertJsonFragment([
            'total' => 10,
            'per_page' => 1,
            'current_page' => 2,
        ]);
    }
}
