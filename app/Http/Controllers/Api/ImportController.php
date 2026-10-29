<?php

namespace App\Http\Controllers\Api;

use App\Domains\Import\ManageImport\Services\InitiateImport;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Import\StoreImportRequest;
use App\Http\Resources\ImportJobResource;
use App\Models\ImportError;
use App\Models\ImportJob;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ImportController extends ApiController
{
   
    public function store(StoreImportRequest $request)
    {
        $file = $request->file('file');
        
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $request->input('vault_id'),
            'filename' => $file->getClientOriginalName(),
            'file_path' => $file->store('imports'),
        ];

        $importJob = (new InitiateImport)->execute($data);

        return (new ImportJobResource($importJob))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

   
    public function show(Request $request, string $id)
    {
        $importJob = ImportJob::where('account_id', $request->user()->account_id)
            ->findOrFail($id);

        return new ImportJobResource($importJob);
    }

    public function destroy(Request $request, string $id)
    {
        $importJob = ImportJob::where('account_id', $request->user()->account_id)
            ->findOrFail($id);

        if (in_array($importJob->status, ['pending', 'processing'])) {
            $importJob->status = 'cancelled';
            $importJob->save();
        }

        return response()->json([
            'message' => 'Import job cancelled'
        ]);
    }

    public function downloadErrors(Request $request, string $id)
    {
        $importJob = ImportJob::where('account_id', $request->user()->account_id)
            ->findOrFail($id);

        $errors = ImportError::where('import_job_id', $importJob->id)
            ->orderBy('row_number')
            ->get();

        if ($errors->isEmpty()) {
            return response()->json([
                'message' => 'No errors found for this import.'
            ], 404);
        }

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$importJob->filename}_errors.csv",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($errors) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['row_number', 'error_message']);

            foreach ($errors as $error) {
                fputcsv($file, [$error->row_number, $error->error_message]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
