<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Helpers\DateHelper;
use Illuminate\Support\Carbon;
use App\Jobs\ExportAccountAsSQL;


class ApiExportController extends ApiController
{
    /**
    * Exports the data of the account in SQL format.
    *
    * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response|null
    */
    public function exportToSql(){
        $path = ExportAccountAsSQL::dispatchSync();

        $adapter = disk_adapter(ExportAccountAsSQL::STORAGE);

        $exportdate = Carbon::now(DateHelper::getTimezone())->format('Y-m-d');

        return response()
            ->download($adapter->getPathPrefix().$path, "monica-export.$exportdate.sql")
            ->deleteFileAfterSend(true);
    }
}
