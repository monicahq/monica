<?php

declare(strict_types=1);

namespace App\Contracts;

interface SourceFormatVisitor
{
    public function visitMonica(): void;

    public function visitMonicaFour(): void;
}
