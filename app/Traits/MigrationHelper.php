<?php

namespace App\Traits;

use Illuminate\Support\Fluent;

trait MigrationHelper
{
    abstract public function getConnection();

    /**
     * Add default value for sqlite.
     *
     * @return void
     */
    public function default(Fluent $fluent, $value)
    {
        if ($this->getDriverName() == 'sqlite') {
            return $fluent->default($value);
        }
        return $fluent;
    }

    /**
     * Get current driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return \DB::connection()->getDriverName();
    }
}
