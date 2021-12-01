<?php

namespace App\Http\Controllers\Api\Settings;

use App\Traits\SQLExporter;
use App\Http\Controllers\Api\ApiController;

class ApiExportController extends ApiController
{
    use SQLExporter;
}
