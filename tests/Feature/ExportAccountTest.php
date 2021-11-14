<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Jobs\ExportAccount;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\AssertableJsonString;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportAccountTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_exports_account_file()
    {
        Storage::fake();
        Storage::fake('local');

        $user = $this->signIn();

        $path = ExportAccount::dispatchSync();

        $this->assertStringStartsWith('exports/', $path);
        $this->assertStringEndsWith('.sql', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_exports_json_file()
    {
        Storage::fake();
        Storage::fake('local');

        $user = $this->signIn();

        $path = ExportAccount::dispatchSync(ExportAccount::JSON);

        $this->assertStringStartsWith('exports/', $path);
        $this->assertStringEndsWith('.json', $path);
        Storage::disk('public')->assertExists($path);

        $json = Storage::disk('public')->get($path);
        $test = new AssertableJsonString($json);

        $test->assertStructure([
            'account' => [
                'uuid',
                'created_at',
                'updated_at',
                'data' => [
                    '*' => [
                        'count',
                        'type',
                        'values' => [
                            '*' => [
                                'uuid',
                                'created_at',
                                'updated_at',
                                'properties',
                            ],
                        ],
                    ],
                ],
                'properties' => [
                    'modules' => [
                        '*' => [
                            'key',
                            'translation_key',
                            'created_at',
                            'updated_at',
                            'properties',
                        ],
                    ],
                    'reminder_rules' => [
                        '*' => [
                            'number_of_days_before',
                            'created_at',
                            'updated_at',
                            'properties',
                        ],
                    ],
                ],
                'instance' => [
                    'activity_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'translation_key',
                                'category',
                            ],
                        ],
                    ],
                    'activity_type_categories' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'translation_key',
                            ],
                        ],
                    ],
                    'life_event_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'default_life_event_type_key',
                                'core_monica_data',
                                'category',
                            ],
                        ],
                    ],
                    'life_event_categories' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'default_life_event_category_key',
                                'core_monica_data',
                            ],
                        ],
                    ],
                    'contact_field_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'fontawesome_icon',
                                'delible',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function it_exports_json_file_contacts()
    {
        Storage::fake();
        Storage::fake('local');

        $user = $this->signIn();

        factory(Contact::class, 5)->create([
            'account_id' => $user->account->id,
        ]);

        $path = ExportAccount::dispatchSync(ExportAccount::JSON);

        $this->assertStringStartsWith('exports/', $path);
        $this->assertStringEndsWith('.json', $path);
        Storage::disk('public')->assertExists($path);

        $json = Storage::disk('public')->get($path);
        $test = new AssertableJsonString($json);

        $test->assertStructure([
            'account' => [
                'uuid',
                'created_at',
                'updated_at',
                'data' => [
                    '*' => [
                        'count',
                        'type',
                        'values' => [
                            '*' => [
                                'uuid',
                                'created_at',
                                'updated_at',
                                'properties',
                            ],
                        ],
                    ],
                ],
                'properties' => [
                    'modules' => [
                        '*' => [
                            'key',
                            'translation_key',
                            'created_at',
                            'updated_at',
                            'properties',
                        ],
                    ],
                    'reminder_rules' => [
                        '*' => [
                            'number_of_days_before',
                            'created_at',
                            'updated_at',
                            'properties',
                        ],
                    ],
                ],
                'instance' => [
                    'activity_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'translation_key',
                                'category',
                            ],
                        ],
                    ],
                    'activity_type_categories' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'translation_key',
                            ],
                        ],
                    ],
                    'life_event_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'default_life_event_type_key',
                                'core_monica_data',
                                'category',
                            ],
                        ],
                    ],
                    'life_event_categories' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'default_life_event_category_key',
                                'core_monica_data',
                            ],
                        ],
                    ],
                    'contact_field_types' => [
                        '*' => [
                            'uuid',
                            'created_at',
                            'updated_at',
                            'properties' => [
                                'name',
                                'fontawesome_icon',
                                'delible',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
