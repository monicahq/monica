<?php

namespace App\Domains\Contact\ManageDocuments\Events;

use App\Models\File;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileDeleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public File $file;

    /**
     * Create a new event instance.
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }
}
