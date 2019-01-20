<?php

namespace App\Models\DAV\Backends\CalDAV;

use App\Models\DAV\Backends\IDAVBackend;
use App\Models\DAV\Backends\AbstractDAVBackend;

abstract class AbstractCalDAVBackend implements ICalDAVBackend, IDAVBackend
{
    use AbstractDAVBackend;
}
