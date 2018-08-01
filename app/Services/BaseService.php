<?php

namespace App\Services;

class BaseService
{
    public function validateDataStructure(array $data, array $structure)
    {
        foreach ($structure as $structKey => $structValue) {
            $found = false;

            foreach ($data as $key => $value) {
                if ($key == $structValue) {
                    $found = true;
                }
            }

            if (! $found) {
                return false;
            }
        }

        return true;
    }
}
