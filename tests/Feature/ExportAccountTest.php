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

    public function test_it_exports_account_file()
    {
        Storage::fake();
        Storage::fake('local');

        $user = $this->signIn();

        $path = ExportAccount::dispatchSync();

        $this->assertStringStartsWith('exports/', $path);
        $this->assertStringEndsWith('.sql', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_it_exports_json_file()
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
                'users' => [
                    '*' => [
                        'uuid',
                        'first_name',
                        'last_name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                        'properties' => [
                            'locale',
                            'metric',
                            'fluid_container',
                            'contacts_sort_order',
                            'name_order',
                            'dashboard_active_tab',
                            'gifts_active_tab',
                            'profile_active_tab',
                            'timezone',
                            'profile_new_life_event_badge_seen',
                            'temperature_scale',
                            'currency',
                        ],
                    ],
                ],
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
        ]);
    }

    public function test_it_exports_json_file_contacts()
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
                'users' => [
                    '*' => [
                        'uuid',
                        'first_name',
                        'last_name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                        'properties' => [
                            'locale',
                            'metric',
                            'fluid_container',
                            'contacts_sort_order',
                            'name_order',
                            'dashboard_active_tab',
                            'gifts_active_tab',
                            'profile_active_tab',
                            'timezone',
                            'profile_new_life_event_badge_seen',
                            'temperature_scale',
                            'currency',
                        ],
                    ],
                ],
                'contacts' => [
                    '*' => [
                        'uuid',
                        'created_at',
                        'updated_at',
                        'properties' => [
                            'first_name',
                            'last_name',
                            'is_starred',
                            'is_partial',
                            'is_active',
                            'is_dead',
                            'number_of_views',
                            'avatar' => [
                                'avatar_source',
                                'avatar_gravatar_url',
                                'avatar_adorable_uuid',
                                'avatar_adorable_url',
                                'avatar_default_url',
                                'has_avatar',
                                'avatar_external_url',
                                'avatar_file_name',
                                'avatar_location',
                                'gravatar_url',
                                'default_avatar_color',
                            ],
                            'gender',
                        ],
                    ],
                ],
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
        ]);
    }
}
