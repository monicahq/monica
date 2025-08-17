<?php

namespace App\Domains\Contact\Dav\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use Closure;
use Sabre\VObject\Document;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;

class ReadVObject extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'entry' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! is_string($value) && ! is_resource($value)) {
                        $fail($attribute.' must be a string or a resource.');
                    }
                },
            ],
        ];
    }

    /**
     * Import one VCard.
     */
    public function execute(array $data): ?Document
    {
        $this->validateRules($data);

        try {
            return Reader::read($data['entry'], Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
        } catch (ParseException $e) {
            return null;
        }
    }
}
