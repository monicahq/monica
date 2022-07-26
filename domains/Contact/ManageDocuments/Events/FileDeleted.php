<?php

namespace App\Contact\ManageDocuments\Events;

use App\Models\File;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

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
