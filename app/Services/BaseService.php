<?php

namespace App\Services;

class BaseService
{
    /**
     * Check if an array has a given structure.
     *
     * @param  array  $data
     * @param  array  $structure
     * @return bool
     */
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
