<?php

namespace App\LaraDB\Controllers;

use App\Http\Controllers\Controller;
use App\LaraDB\Helpers\DatabaseViewHelper;
use App\LaraDB\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DBController extends Controller
{
    public function index(Request $request)
    {
        $tables = Schema::getTables();
        $firstTable = new Table($tables[0]['name']);

        if ($request->input('table')) {
            $firstTable = new Table($request->input('table'));
        }

        $tablesCollection = DatabaseViewHelper::getTablesInformation($tables);
        $rows = $firstTable->getColumnValues(0);

        return view('db.index', [
            'database' => DatabaseViewHelper::getDatabaseInformation(),
            'tables' => $tablesCollection,
            'rows' => $rows,
            'requestedTable' => $firstTable->name,
        ]);
    }
}
