<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Mail\Mailables\Envelope;

interface HasEnvelope
{
    public function envelope(): Envelope;
}
