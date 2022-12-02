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
    public const TYPE_ACTIVITIES = 'activities';

    public const TYPE_NEW_JOB = 'new_job';

    public const TYPE_RETIREMENT = 'retirement';

    public const TYPE_NEW_SCHOOL = 'new_school';

    public const TYPE_STUDY_ABROAD = 'study_abroad';

    public const TYPE_VOLUNTEER_WORK = 'volunteer_work';

    public const TYPE_PUBLISHED_BOOK_OR_PAPER = 'published_book_or_paper';

    public const TYPE_MILITARY_SERVICE = 'military_service';

    public const TYPE_FIRST_MET = 'first_met';

    public const TYPE_NEW_RELATIONSHIP = 'new_relationship';

    public const TYPE_ENGAGEMENT = 'engagement';

    public const TYPE_MARRIAGE = 'marriage';

    public const TYPE_ANNIVERSARY = 'anniversary';

    public const TYPE_EXPECTING_A_BABY = 'expecting_a_baby';

    public const TYPE_NEW_CHILD = 'new_child';

    public const TYPE_NEW_FAMILY_MEMBER = 'new_family_member';

    public const TYPE_NEW_PET = 'new_pet';

    public const TYPE_END_OF_RELATIONSHIP = 'end_of_relationship';

    public const TYPE_LOSS_OF_A_LOVED_ONE = 'loss_of_a_loved_one';

    public const TYPE_MOVED = 'moved';

    public const TYPE_BOUGHT_A_HOME = 'bought_a_home';

    public const TYPE_HOME_IMPROVEMENT = 'home_improvement';

    public const TYPE_HOLIDAYS = 'holidays';

    public const TYPE_NEW_VEHICLE = 'new_vehicle';

    public const TYPE_NEW_ROOMMATE = 'new_roommate';

    public const TYPE_OVERCAME_AN_ILLNESS = 'overcame_an_illness';

    public const TYPE_QUIT_A_HABIT = 'quit_a_habit';

    public const TYPE_NEW_EATING_HABITS = 'new_eating_habits';

    public const TYPE_WEIGHT_LOSS = 'weight_loss';

    public const TYPE_WEAR_GLASS_OR_CONTACT = 'wear_glass_or_contact';

    public const TYPE_BROKEN_BONE = 'broken_bone';

    public const TYPE_REMOVED_BRACES = 'removed_braces';

    public const TYPE_SURGERY = 'surgery';

    public const TYPE_DENTIST = 'dentist';

    public const TYPE_NEW_SPORT = 'new_sport';

    public const TYPE_NEW_HOBBY = 'new_hobby';

    public const TYPE_NEW_INSTRUMENT = 'new_instrument';

    public const TYPE_NEW_LANGUAGE = 'new_language';

    public const TYPE_TATTOO_OR_PIERCING = 'tattoo_or_piercing';

    public const TYPE_NEW_LICENSE = 'new_license';

    public const TYPE_TRAVEL = 'travel';

    public const TYPE_ACHIEVEMENT_OR_AWARD = 'achievement_or_award';

    public const TYPE_CHANGED_BELIEFS = 'changed_beliefs';

    public const TYPE_FIRST_WORD = 'first_word';

    public const TYPE_FIRST_KISS = 'first_kiss';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
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
     * @return Attribute<string,never>
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
