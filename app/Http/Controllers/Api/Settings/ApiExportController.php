<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Traits\SQLExporter;


class ApiExportController extends ApiController
{
    use SQLExporter;
}
