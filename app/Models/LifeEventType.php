<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LifeEventType extends Model
{
    use HasFactory;

    protected $table = 'life_event_types';

    /**
     * Possible types.
     */
    const TYPE_ACTIVITIES = 'activities';
    const TYPE_NEW_JOB = 'new_job';
    const TYPE_RETIREMENT = 'retirement';
    const TYPE_NEW_SCHOOL = 'new_school';
    const TYPE_STUDY_ABROAD = 'study_abroad';
    const TYPE_VOLUNTEER_WORK = 'volunteer_work';
    const TYPE_PUBLISHED_BOOK_OR_PAPER = 'published_book_or_paper';
    const TYPE_MILITARY_SERVICE = 'military_service';
    const TYPE_FIRST_MET = 'first_met';
    const TYPE_NEW_RELATIONSHIP = 'new_relationship';
    const TYPE_ENGAGEMENT = 'engagement';
    const TYPE_MARRIAGE = 'marriage';
    const TYPE_ANNIVERSARY = 'anniversary';
    const TYPE_EXPECTING_A_BABY = 'expecting_a_baby';
    const TYPE_NEW_CHILD = 'new_child';
    const TYPE_NEW_FAMILY_MEMBER = 'new_family_member';
    const TYPE_NEW_PET = 'new_pet';
    const TYPE_END_OF_RELATIONSHIP = 'end_of_relationship';
    const TYPE_LOSS_OF_A_LOVED_ONE = 'loss_of_a_loved_one';
    const TYPE_MOVED = 'moved';
    const TYPE_BOUGHT_A_HOME = 'bought_a_home';
    const TYPE_HOME_IMPROVEMENT = 'home_improvement';
    const TYPE_HOLIDAYS = 'holidays';
    const TYPE_NEW_VEHICLE = 'new_vehicle';
    const TYPE_NEW_ROOMMATE = 'new_roommate';
    const TYPE_OVERCAME_AN_ILLNESS = 'overcame_an_illness';
    const TYPE_QUIT_A_HABIT = 'quit_a_habit';
    const TYPE_NEW_EATING_HABITS = 'new_eating_habits';
    const TYPE_WEIGHT_LOSS = 'weight_loss';
    const TYPE_WEAR_GLASS_OR_CONTACT = 'wear_glass_or_contact';
    const TYPE_BROKEN_BONE = 'broken_bone';
    const TYPE_REMOVED_BRACES = 'removed_braces';
    const TYPE_SURGERY = 'surgery';
    const TYPE_DENTIST = 'dentist';
    const TYPE_NEW_SPORT = 'new_sport';
    const TYPE_NEW_HOBBY = 'new_hobby';
    const TYPE_NEW_INSTRUMENT = 'new_instrument';
    const TYPE_NEW_LANGUAGE = 'new_language';
    const TYPE_TATTOO_OR_PIERCING = 'tattoo_or_piercing';
    const TYPE_NEW_LICENSE = 'new_license';
    const TYPE_TRAVEL = 'travel';
    const TYPE_ACHIEVEMENT_OR_AWARD = 'achievement_or_award';
    const TYPE_CHANGED_BELIEFS = 'changed_beliefs';
    const TYPE_FIRST_WORD = 'first_word';
    const TYPE_FIRST_KISS = 'first_kiss';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'life_event_category_id',
        'label',
        'label_translation_key',
        'can_be_deleted',
        'type',
        'position',
    ];

    /**
     * Get the life event category associated with the life event type.
     *
     * @return BelongsTo
     */
    public function lifeEventCategory(): BelongsTo
    {
        return $this->belongsTo(LifeEventCategory::class, 'life_event_category_id');
    }

    /**
     * Get the life events associated with the life event type.
     *
     * @return HasMany
     */
    public function lifeEvents(): HasMany
    {
        return $this->hasMany(ContactLifeEvent::class);
    }

    /**
     * Get the label attribute.
     * Life Event categories have a default label that can be translated.
     * Howerer, if a label is set, it will be used instead of the default.
     *
     * @return Attribute
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $value) {
                    return trans($attributes['label_translation_key']);
                }

                return $value;
            }
        );
    }
}
