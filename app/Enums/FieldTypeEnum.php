<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The kinds of field a section can hold.
 *
 * These are the ones the first version ships with. The catalogue names more
 * (tags, duration, money, a full name, a rating, rich text, a colour, a
 * location, a computed value, a qualified relation), and each of them arrives
 * with the storage and the rendering it needs rather than as a string somebody
 * can already write into the column.
 *
 * The rule that makes the catalogue worth having is that a type renders
 * accessibly by construction: the label bound to the input, the error
 * announced, the required state programmatic and the whole thing usable with a
 * keyboard alone. That is implemented once per type, here, and not checked
 * again for every template anybody builds.
 */
enum FieldTypeEnum: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Boolean = 'boolean';
    case Url = 'url';
    case Email = 'email';
    case Phone = 'phone';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Radio = 'radio';
    case Date = 'date';
    case RecurringDate = 'recurring_date';
    case Relation = 'relation';
    case File = 'file';
    case Image = 'image';
    case Avatar = 'avatar';
    case Address = 'address';
    case Repeater = 'repeater';
    case Divider = 'divider';
    case Heading = 'heading';
    case Help = 'help';

    /**
     * Whether anything is ever stored for this field. The layout types are
     * there to break a long page up, and they carry no value row, which is also
     * what makes them invisible to search, to export and to a share link.
     */
    public function storesValue(): bool
    {
        return ! in_array($this, [self::Divider, self::Heading, self::Help], true);
    }

    /**
     * Which column of the value table this type writes to. Exactly one of them
     * is populated for any stored value, and which one is a property of the
     * type rather than a choice made per row.
     *
     * Three separate things read as null here. The layout types store nothing
     * at all. A repeater holds repetitions rather than a value of its own, so
     * its children are what write. And a relation is decided by what the field
     * points at rather than by its type, so the action that writes it reads
     * `relation_target` instead.
     */
    public function valueColumn(): ?string
    {
        return match ($this) {
            self::Text, self::Url, self::Email, self::Phone => 'value_string',
            self::Textarea => 'value_text',
            self::Number => 'value_number',
            self::Boolean => 'value_boolean',
            self::Date, self::RecurringDate => 'value_date',
            self::Select, self::Radio => 'value_list_item_id',
            self::Multiselect => 'value_list_item_ids',
            self::File, self::Image, self::Avatar => 'value_file_id',
            self::Address => 'value_json',
            self::Relation, self::Repeater, self::Divider, self::Heading, self::Help => null,
        };
    }

    /**
     * Whether a form can offer a control for this type yet.
     *
     * It is not a property of the type so much as a property of how far the
     * interface has got, and it is here because three separate things have to
     * agree about it: the page that draws the inputs, the rules that validate
     * them and the action that writes them. A type answering false is stored
     * and read perfectly well; what it has no screen for is being typed in.
     *
     * Each of the remaining ones needs machinery of its own rather than another
     * input: a relation needs a search across the vault, a repeater needs the
     * whole business of adding and reordering repetitions, an address is
     * several inputs that travel together, and the file types need an upload.
     */
    public function hasAnInput(): bool
    {
        return in_array($this, [
            self::Text, self::Textarea, self::Number, self::Boolean, self::Url,
            self::Email, self::Phone, self::Select, self::Multiselect, self::Radio,
            self::Date, self::RecurringDate,
        ], true);
    }

    /**
     * Whether the field draws its choices from a value list, which is what
     * spares somebody retyping the same handful of options into every field
     * that offers them.
     */
    public function usesValueList(): bool
    {
        return in_array($this, [self::Select, self::Multiselect, self::Radio], true);
    }

    /**
     * Whether the field points at another record, and therefore has to say what
     * kind of record it points at.
     */
    public function isRelation(): bool
    {
        return $this === self::Relation;
    }

    /**
     * Whether a reminder rule may read from this field. Any recurring date can,
     * not only a birthday: an anniversary, the date a visa expires, the month a
     * subscription renews. Nothing in the reminder machinery is allowed to
     * special case one of them.
     */
    public function canDriveAReminder(): bool
    {
        return $this === self::RecurringDate;
    }

    /**
     * Whether the field is a section that can be filled several times over, which
     * is the one type other fields may sit inside.
     */
    public function isRepeater(): bool
    {
        return $this === self::Repeater;
    }
}
