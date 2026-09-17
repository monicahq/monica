<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

/**
 * The natures an activity can be recorded as that the product supplies, and the custom fields each
 * of them asks for.
 *
 * The date, the time, the duration, the title, the account of what happened, the place and the
 * people are carried by the activity itself, so no type asks for them again. What is described here
 * is only what one nature needs beyond that.
 *
 * A block whose name is null holds the fields that are in no group. It is read before every named
 * group, which is the order the field editor uses.
 */
enum ActivityTypeEnum: string implements Translatable
{
    case Call = 'call';
    case Message = 'message';
    case Meal = 'meal';
    case Visit = 'visit';
    case TimeTogether = 'time_together';
    case Argument = 'argument';
    case Marriage = 'marriage';
    case Birth = 'birth';
    case Separation = 'separation';
    case Meeting = 'meeting';
    case Interview = 'interview';
    case NewJob = 'new_job';
    case SchoolClass = 'class';
    case Training = 'training';
    case Graduation = 'graduation';
    case MedicalAppointment = 'medical_appointment';
    case Therapy = 'therapy';
    case Illness = 'illness';
    case HospitalStay = 'hospital_stay';
    case Exercise = 'exercise';
    case Move = 'move';
    case HomeImprovement = 'home_improvement';
    case MajorPurchase = 'major_purchase';
    case HouseholdTask = 'household_task';
    case Restaurant = 'restaurant';
    case Concert = 'concert';
    case Cinema = 'cinema';
    case Reading = 'reading';
    case Game = 'game';
    case Hobby = 'hobby';
    case Trip = 'trip';
    case Holiday = 'holiday';
    case Hike = 'hike';
    case Excursion = 'excursion';
    case Reflection = 'reflection';
    case Decision = 'decision';
    case Achievement = 'achievement';
    case Memory = 'memory';
    case SpiritualEvent = 'spiritual_event';
    case OtherActivity = 'other_activity';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Call',
            self::Message => 'Message',
            self::Meal => 'Meal',
            self::Visit => 'Visit',
            self::TimeTogether => 'Time together',
            self::Argument => 'Argument',
            self::Marriage => 'Marriage',
            self::Birth => 'Birth',
            self::Separation => 'Separation',
            self::Meeting => 'Meeting',
            self::Interview => 'Interview',
            self::NewJob => 'New job',
            self::SchoolClass => 'Class',
            self::Training => 'Training',
            self::Graduation => 'Graduation',
            self::MedicalAppointment => 'Medical appointment',
            self::Therapy => 'Therapy',
            self::Illness => 'Illness',
            self::HospitalStay => 'Hospital stay',
            self::Exercise => 'Exercise',
            self::Move => 'Move',
            self::HomeImprovement => 'Home improvement',
            self::MajorPurchase => 'Major purchase',
            self::HouseholdTask => 'Household task',
            self::Restaurant => 'Restaurant',
            self::Concert => 'Concert',
            self::Cinema => 'Cinema',
            self::Reading => 'Reading',
            self::Game => 'Game',
            self::Hobby => 'Hobby',
            self::Trip => 'Trip',
            self::Holiday => 'Holiday',
            self::Hike => 'Hike',
            self::Excursion => 'Excursion',
            self::Reflection => 'Reflection',
            self::Decision => 'Decision',
            self::Achievement => 'Achievement',
            self::Memory => 'Memory',
            self::SpiritualEvent => 'Spiritual event',
            self::OtherActivity => 'Other activity',
        };
    }

    public function category(): ActivityCategoryEnum
    {
        return match ($this) {
            self::Call, self::Message, self::Meal, self::Visit, self::TimeTogether,
            self::Argument, self::Marriage, self::Birth, self::Separation => ActivityCategoryEnum::RelationshipsAndFamily,
            self::Meeting, self::Interview, self::NewJob, self::SchoolClass,
            self::Training, self::Graduation => ActivityCategoryEnum::WorkAndEducation,
            self::MedicalAppointment, self::Therapy, self::Illness,
            self::HospitalStay, self::Exercise => ActivityCategoryEnum::HealthAndWellbeing,
            self::Move, self::HomeImprovement, self::MajorPurchase,
            self::HouseholdTask => ActivityCategoryEnum::HomeAndDailyLife,
            self::Restaurant, self::Concert, self::Cinema, self::Reading,
            self::Game, self::Hobby => ActivityCategoryEnum::LeisureAndCulture,
            self::Trip, self::Holiday, self::Hike, self::Excursion => ActivityCategoryEnum::TravelAndExperiences,
            self::Reflection, self::Decision, self::Achievement,
            self::Memory, self::SpiritualEvent => ActivityCategoryEnum::Personal,
            self::OtherActivity => ActivityCategoryEnum::Other,
        };
    }

    /**
     * @return list<array{name: ?string, fields: list<array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}>}>
     */
    public function blocks(): array
    {
        return match ($this) {
            self::Call => [self::loose([
                self::choice('channel', 'Channel', ['Phone', 'Video', 'Voice call in an app']),
                self::choice('direction', 'Direction', ['Incoming', 'Outgoing']),
                self::note(),
            ])],
            self::Message => [self::loose([
                self::choice('channel', 'Channel', ['Text message', 'Email', 'Messaging app', 'Social network', 'Other']),
                self::choice('direction', 'Direction', ['Sent', 'Received']),
                self::note(),
            ])],
            self::Meal => [self::loose([
                self::choice('meal', 'Meal', ['Breakfast', 'Lunch', 'Dinner', 'Snack']),
                self::text('occasion', 'Occasion'),
                self::note(),
            ])],
            self::Visit => [self::loose([
                self::text('reason', 'Reason'),
                self::note(),
            ])],
            self::TimeTogether => [self::loose([
                self::note(),
            ])],
            self::Argument => [self::loose([
                self::text('subject', 'Subject of the disagreement'),
                self::boolean('settled', 'Settled'),
                self::note(),
            ])],
            self::Marriage => [self::loose([
                self::choice('ceremony', 'Kind of ceremony', ['Civil', 'Religious', 'Other']),
                self::file('document', 'Document'),
                self::note(),
            ])],
            self::Birth => [self::loose([
                self::note(),
            ])],
            self::Separation => [self::loose([
                self::note(),
            ])],
            self::Meeting => [
                self::group('Preparation', [
                    self::textarea('agenda', 'Agenda'),
                ]),
                self::group('Results', [
                    self::textarea('decisions', 'Decisions'),
                    self::textarea('actions', 'Agreed actions'),
                    self::note(),
                ]),
            ],
            self::Interview => [
                self::group('Position', [
                    self::text('job_title', 'Job title'),
                    self::text('organisation', 'Organisation'),
                ]),
                self::group('Process', [
                    self::choice('stage', 'Stage', ['First conversation', 'Interview', 'Exercise', 'Final interview', 'Other']),
                    self::textarea('feedback', 'Feedback received'),
                    self::note(),
                ]),
            ],
            self::NewJob => [self::loose([
                self::text('job_title', 'Job title'),
                self::text('employer', 'Employer'),
                self::choice('arrangement', 'Working arrangement', ['Employed', 'Self-employed', 'Other']),
                self::note(),
            ])],
            self::SchoolClass => [self::loose([
                self::text('subject', 'Class or subject'),
                self::text('teacher', 'Teacher'),
                self::file('material', 'Course material'),
                self::note(),
            ])],
            self::Training => [self::loose([
                self::text('programme', 'Programme'),
                self::text('provider', 'Provider'),
                self::file('certificate', 'Certificate'),
                self::note(),
            ])],
            self::Graduation => [self::loose([
                self::text('qualification', 'Degree or qualification'),
                self::text('institution', 'Institution'),
                self::file('proof', 'Proof'),
                self::note(),
            ])],
            self::MedicalAppointment => [
                self::group('Consultation', [
                    self::text('reason', 'Reason'),
                    self::text('practitioner', 'Practitioner'),
                    self::note(),
                ]),
                self::group('Follow-up', [
                    self::textarea('instructions', 'Instructions'),
                    self::file('report', 'Prescription or report'),
                    self::date('next_check', 'Next check-up'),
                ]),
            ],
            self::Therapy => [
                self::group('Session', [
                    self::text('therapist', 'Therapist'),
                    self::textarea('topics', 'Topics covered'),
                    self::note(),
                ]),
                self::group('Follow-up', [
                    self::textarea('exercise', 'Exercise or avenue to explore'),
                ]),
            ],
            self::Illness => [
                self::group('State of health', [
                    self::text('diagnosis', 'Diagnosis'),
                    self::textarea('symptoms', 'Symptoms'),
                    self::note(),
                ]),
                self::group('Care', [
                    self::textarea('treatment', 'Treatment'),
                    self::file('document', 'Medical document'),
                ]),
            ],
            self::HospitalStay => [
                self::group('Stay', [
                    self::text('hospital', 'Hospital'),
                    self::text('ward', 'Ward'),
                    self::text('reason', 'Reason'),
                    self::note(),
                ]),
                self::group('Documents', [
                    self::file('report', 'Discharge report'),
                ]),
            ],
            self::Exercise => [self::loose([
                self::text('discipline', 'Discipline'),
                self::number('distance', 'Distance in kilometres', ['unit' => 'km', 'min' => 0]),
                self::choice('intensity', 'Intensity', ['Gentle', 'Moderate', 'Intense']),
                self::note(),
            ])],
            self::Move => [
                self::loose([
                    self::note(),
                ]),
                self::group('Addresses', [
                    self::text('previous_address', 'Previous address'),
                    self::text('new_address', 'New address'),
                ]),
                self::group('Documents', [
                    self::file('document', 'Proof or contract'),
                ]),
            ],
            self::HomeImprovement => [
                self::group('Project', [
                    self::text('room', 'Room or space'),
                    self::textarea('work', 'Work done'),
                    self::text('contractor', 'Contractor'),
                    self::note(),
                ]),
                self::group('Documents', [
                    self::file('invoice', 'Invoice'),
                ]),
            ],
            self::MajorPurchase => [
                self::group('Purchase', [
                    self::text('item', 'Item'),
                    self::text('seller', 'Seller'),
                    self::text('price', 'Price with currency'),
                    self::note(),
                ]),
                self::group('Warranty', [
                    self::date('warranty_end', 'End of the warranty'),
                    self::file('receipt', 'Receipt or warranty'),
                ]),
            ],
            self::HouseholdTask => [self::loose([
                self::note(),
            ])],
            self::Restaurant => [self::loose([
                self::text('dish', 'Dish or menu'),
                self::number('rating', 'Rating out of 5', ['min' => 1, 'max' => 5, 'decimals' => 0]),
                self::image('photo', 'Photo'),
                self::note(),
            ])],
            self::Concert => [self::loose([
                self::text('artist', 'Artist'),
                self::text('song', 'Standout song'),
                self::file('ticket', 'Ticket'),
                self::note(),
            ])],
            self::Cinema => [self::loose([
                self::text('film', 'Film'),
                self::number('rating', 'Rating out of 5', ['min' => 1, 'max' => 5, 'decimals' => 0]),
                self::note(),
            ])],
            self::Reading => [self::loose([
                self::text('book', 'Book'),
                self::text('author', 'Author'),
                self::number('pages', 'Pages read', ['min' => 0, 'decimals' => 0]),
                self::boolean('finished', 'Finished'),
                self::note(),
            ])],
            self::Game => [self::loose([
                self::text('game', 'Game'),
                self::choice('kind', 'Kind of game', ['Board game', 'Video game', 'Card game', 'Role-playing game', 'Other']),
                self::text('result', 'Result'),
                self::note(),
            ])],
            self::Hobby => [self::loose([
                self::text('practice', 'What was practised'),
                self::text('project', 'Project'),
                self::image('outcome', 'What was made'),
                self::note(),
            ])],
            self::Trip => [self::loose([
                self::choice('mode', 'Mode of travel', ['Car', 'Train', 'Plane', 'Boat', 'Other']),
                self::text('reference', 'Booking reference'),
                self::file('ticket', 'Ticket'),
                self::note(),
            ])],
            self::Holiday => [self::loose([
                self::text('accommodation', 'Accommodation'),
                self::text('reference', 'Booking reference'),
                self::file('confirmation', 'Confirmation'),
                self::note(),
            ])],
            self::Hike => [self::loose([
                self::number('distance', 'Distance in kilometres', ['unit' => 'km', 'min' => 0]),
                self::number('ascent', 'Ascent in metres', ['unit' => 'm', 'min' => 0]),
                self::choice('difficulty', 'Difficulty', ['Easy', 'Moderate', 'Hard']),
                self::file('track', 'Track'),
                self::note(),
            ])],
            self::Excursion => [self::loose([
                self::text('organiser', 'Organiser'),
                self::text('reference', 'Booking reference'),
                self::file('ticket', 'Ticket'),
                self::note(),
            ])],
            self::Reflection => [],
            self::Decision => [
                self::group('Choice', [
                    self::text('subject', 'Subject'),
                    self::textarea('options', 'Options considered'),
                    self::textarea('reason', 'Reason for the choice'),
                ]),
                self::group('Follow-up', [
                    self::date('review_date', 'Date to look at it again'),
                    self::note(),
                ]),
            ],
            self::Achievement => [self::loose([
                self::text('area', 'Area'),
                self::text('result', 'Result'),
                self::file('proof', 'Proof'),
                self::note(),
            ])],
            self::Memory => [self::loose([
                self::text('source', 'Where the memory comes from'),
                self::image('photo', 'Photo'),
            ])],
            self::SpiritualEvent => [self::loose([
                self::text('tradition', 'Tradition or setting'),
                self::text('practice', 'Ceremony or practice'),
                self::note(),
            ])],
            self::OtherActivity => [],
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        $keys = [$this->label()];

        foreach ($this->blocks() as $block) {
            if ($block['name'] !== null) {
                $keys[] = $block['name'];
            }

            foreach ($block['fields'] as $field) {
                $keys[] = $field['label'];

                foreach ($field['choices'] as $choice) {
                    $keys[] = $choice;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  list<array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}>  $fields
     * @return array{name: ?string, fields: list<array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}>}
     */
    private static function loose(array $fields): array
    {
        return ['name' => null, 'fields' => $fields];
    }

    /**
     * @param  list<array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}>  $fields
     * @return array{name: ?string, fields: list<array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}>}
     */
    private static function group(string $name, array $fields): array
    {
        return ['name' => $name, 'fields' => $fields];
    }

    /** @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>} */
    private static function text(string $key, string $label): array
    {
        return self::field($key, $label, FieldTypeEnum::Text);
    }

    /** @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>} */
    private static function textarea(string $key, string $label): array
    {
        return self::field($key, $label, FieldTypeEnum::Textarea);
    }

    /** @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>} */
    private static function boolean(string $key, string $label): array
    {
        return self::field($key, $label, FieldTypeEnum::Boolean);
    }

    /** @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>} */
    private static function date(string $key, string $label): array
    {
        return self::field($key, $label, FieldTypeEnum::Date);
    }

    /** @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>} */
    private static function file(string $key, string $label): array
    {
        return self::field($key, $label, FieldTypeEnum::File);
    }

    /** @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>} */
    private static function image(string $key, string $label): array
    {
        return self::field($key, $label, FieldTypeEnum::Image);
    }

    /**
     * @param  array<string, int|string>  $options
     * @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}
     */
    private static function number(string $key, string $label, array $options): array
    {
        return self::field($key, $label, FieldTypeEnum::Number, $options);
    }

    /**
     * @param  list<string>  $choices
     * @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}
     */
    private static function choice(string $key, string $label, array $choices): array
    {
        return self::field($key, $label, FieldTypeEnum::Select, null, $choices);
    }

    /** @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>} */
    private static function note(): array
    {
        return self::field('note', 'Note', FieldTypeEnum::Textarea);
    }

    /**
     * @param  array<string, int|string>|null  $options
     * @param  list<string>  $choices
     * @return array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}
     */
    private static function field(string $key, string $label, FieldTypeEnum $type, ?array $options = null, array $choices = []): array
    {
        return ['key' => $key, 'label' => $label, 'type' => $type, 'options' => $options, 'choices' => $choices];
    }
}
