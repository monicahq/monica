<?php

namespace App\Services\Auth\Population;

use App\Services\BaseService;
use App\Models\Account\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Models\Contact\LifeEventType;
use Illuminate\Database\QueryException;
use App\Models\Contact\LifeEventCategory;

/**
 * Populate life event types and life event categories for a given account.
 * This is typically done when a new account is created.
 */
class PopulateLifeEventsTable extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'migrate_existing_data' => 'required|boolean',
        ];
    }

    /**
     * The data needed for the query to be executed.
     *
     * @var array
     */
    private $data;

    /**
     * Execute the service.
     *
     * @param array $givenData
     * @return bool
     */
    public function execute(array $givenData) : bool
    {
        $this->data = $givenData;

        if (! $this->validate($this->data)) {
            return false;
        }

        $locale = $this->getLocaleOfAccount($this->data['account_id']);
        if (is_null($locale)) {
            return false;
        }

        $this->createEntries($locale);

        $this->markTableAsMigrated();

        return true;
    }

    /**
     * Get the locale associated with the account.
     *
     * @return string|null
     */
    private function getLocaleOfAccount($accountId)
    {
        // get the account
        $account = Account::findOrFail($accountId);

        return $account->getFirstLocale();
    }

    /**
     * Create life event category and life event type entries.
     *
     * @return void
     */
    private function createEntries($locale)
    {
        App::setLocale($locale);

        $defaultLifeEventCategories = $this->getDefaultLifeEventCategories();

        foreach ($defaultLifeEventCategories as $defaultLifeEventCategory) {
            $lifeEventCategory = $this->feedLifeEventCategory($defaultLifeEventCategory);

            $defaultLifeEventTypes = DB::table('default_life_event_types')
                ->where('default_life_event_category_id', $defaultLifeEventCategory->id)
                ->get();

            foreach ($defaultLifeEventTypes as $defaultLifeEventType) {
                $this->feedLifeEventType($defaultLifeEventType, $lifeEventCategory);
            }
        }
    }

    /**
     * Get the default life event categories.
     *
     * @throws QueryException if the query does not run for some reasons.
     * @return Collection
     */
    private function getDefaultLifeEventCategories()
    {
        if ($this->data['migrate_existing_data'] == 1) {
            $defaultLifeEventCategories = DB::table('default_life_event_categories')
                ->get();
        } else {
            $defaultLifeEventCategories = DB::table('default_life_event_categories')
                ->where('migrated', 0)
                ->get();
        }

        return $defaultLifeEventCategories;
    }

    /**
     * Create an entry in the life event category table.
     *
     * @param object $defaultLifeEventCategory
     * @return LifeEventCategory
     */
    private function feedLifeEventCategory($defaultLifeEventCategory): LifeEventCategory
    {
        $lifeEventCategory = LifeEventCategory::create([
            'account_id' => $this->data['account_id'],
            'name' => trans('settings.personalization_life_event_category_'.$defaultLifeEventCategory->translation_key),
            'core_monica_data' => true,
            'default_life_event_category_key' => $defaultLifeEventCategory->translation_key,
        ]);

        return $lifeEventCategory;
    }

    /**
     * Create an entry in the life event type table.
     *
     * @param object $defaultLifeEventType
     * @return void
     */
    private function feedLifeEventType($defaultLifeEventType, $lifeEventCategory)
    {
        LifeEventType::create([
            'account_id' => $this->data['account_id'],
            'life_event_category_id' => $lifeEventCategory->id,
            'name' => trans('settings.personalization_life_event_type_'.$defaultLifeEventType->translation_key),
            'core_monica_data' => true,
            'specific_information_structure' => $defaultLifeEventType->specific_information_structure,
            'default_life_event_type_key' => $defaultLifeEventType->translation_key,
        ]);
    }

    /**
     * Mark the table as migrated.
     *
     * @return void
     */
    private function markTableAsMigrated()
    {
        DB::table('default_life_event_categories')
            ->update(['migrated' => 1]);

        DB::table('default_life_event_types')
            ->update(['migrated' => 1]);
    }
}
