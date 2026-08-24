<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Mail\Mailables\Envelope;

interface HasEnvelope
{
    public function envelope(): Envelope;
}
