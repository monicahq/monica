<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AccountImportReasonEnum: string implements Translatable
{
    case FileNotDecodable = 'file_not_decodable';
    case FileTooLarge = 'file_too_large';
    case FileTypeNotKept = 'file_type_not_kept';
    case ContactNotInTheFile = 'contact_not_in_the_file';
    case ReverseOfAnotherRelationship = 'reverse_of_another_relationship';
    case OneTimeReminderInThePast = 'one_time_reminder_in_the_past';
    case FilesNotOffered = 'files_not_offered';
    case PartlyWritten = 'partly_written';
    case RefusedWhileWriting = 'refused_while_writing';

    public function label(): string
    {
        return match ($this) {
            self::FileNotDecodable => 'The file embedded in the export could not be read.',
            self::FileTooLarge => 'The file is larger than this instance accepts.',
            self::FileTypeNotKept => 'This kind of file cannot be kept in that field.',
            self::ContactNotInTheFile => 'It names somebody the file does not hold.',
            self::ReverseOfAnotherRelationship => 'The same relationship, seen from the other side, came over once.',
            self::OneTimeReminderInThePast => 'A reminder for a single date that has already passed.',
            self::FilesNotOffered => 'This instance does not keep files yet.',
            self::PartlyWritten => 'It came over, but part of it could not be written.',
            self::RefusedWhileWriting => 'It could not be written into the vault.',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
