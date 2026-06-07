<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Domains\Contact\Import\Services\CreateImportJob;
use App\Domains\Contact\Import\Services\CancelImportJob;
use App\Domains\Contact\Import\Services\GetImportJob;
use App\Domains\Contact\Import\Services\GetImportJobErrors;
use App\Domains\Contact\Import\Services\ListImportJobs;
use App\Exceptions\DuplicateImportJobException;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportJobResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends ApiController
{
    /**
     * Override callAction to additionally handle DuplicateImportJobException
     * and return the correct 409 JSON response without leaking the exception.
     *
     * @param  string  $method
     * @param  array   $parameters
     */
    public function callAction($method, $parameters)
    {
        try {
            return parent::callAction($method, $parameters);
        } catch (DuplicateImportJobException $e) {
            return $this->setHTTPStatusCode(409)
                ->setErrorCode(409)
                ->respondWithError($e->getMessage());
        } catch (\App\Exceptions\NotEnoughPermissionException $e) {
            return $this->setHTTPStatusCode(403)
                ->setErrorCode(403)
                ->respondWithError('You do not have enough permissions to access this vault.');
        }
    }

    /**
     * Submit a new CSV import job.
     */
    public function store(StoreImportRequest $request): JsonResponse
    {
        $importJob = (new CreateImportJob)->execute([
            'account_id' => $request->user()->account_id,
            'author_id'  => $request->user()->id,
            'vault_id'   => $request->input('vault_id'),
            'file'       => $request->file('file'),
        ]);

        return (new ImportJobResource($importJob))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * List recent import jobs for the authenticated user's account.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $imports = (new ListImportJobs)->execute([
            'account_id' => $request->user()->account_id,
            'author_id'  => $request->user()->id,
            'vault_id'   => $request->input('vault_id'),
            'per_page'   => $request->input('per_page') ? $request->integer('per_page') : null,
        ]);

        return ImportJobResource::collection($imports);
    }

    /**
     * Show details/progress of a specific import job.
     */
    public function show(Request $request, string $id): ImportJobResource
    {
        $importJob = (new GetImportJob)->execute([
            'account_id'    => $request->user()->account_id,
            'author_id'     => $request->user()->id,
            'import_job_id' => $id,
        ]);

        return new ImportJobResource($importJob);
    }

    /**
     * Cancel a running or pending import job.
     */
    public function cancel(Request $request, string $id): ImportJobResource
    {
        $importJob = (new CancelImportJob)->execute([
            'account_id'    => $request->user()->account_id,
            'author_id'     => $request->user()->id,
            'import_job_id' => $id,
        ]);

        return new ImportJobResource($importJob);
    }

    /**
     * View errors logged for a specific import job (paginated).
     */
    public function errors(Request $request, string $id): JsonResponse
    {
        $result = (new GetImportJobErrors)->execute([
            'account_id'    => $request->user()->account_id,
            'author_id'     => $request->user()->id,
            'import_job_id' => $id,
            'page'          => $request->integer('page', 1),
            'per_page'      => $request->input('per_page') ? $request->integer('per_page') : null,
        ]);

        return $this->respond($result);
    }

    /**
     * Download error details as a reconstructed CSV.
     */
    public function errorsCsv(Request $request, string $id): StreamedResponse
    {
        $importJob = (new GetImportJob)->execute([
            'account_id'    => $request->user()->account_id,
            'author_id'     => $request->user()->id,
            'import_job_id' => $id,
        ]);

        $errors = $importJob->errors()
            ->orderBy('row_number')
            ->get();

        if ($errors->isEmpty()) {
            $callback = function () {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['error_message']);
                fclose($handle);
            };
        } else {
            $headers = [];
            foreach ($errors as $error) {
                foreach (array_keys($error->row_data ?? []) as $header) {
                    if (! in_array($header, $headers, true)) {
                        $headers[] = $header;
                    }
                }
            }
            $headers[] = 'error_message';

            $callback = function () use ($errors, $headers) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);

                foreach ($errors as $error) {
                    $row = [];
                    foreach ($headers as $header) {
                        $row[] = $header === 'error_message'
                            ? $error->error_message
                            : ($error->row_data[$header] ?? '');
                    }
                    fputcsv($handle, $row);
                }

                fclose($handle);
            };
        }

        return response()->streamDownload($callback, "errors-job-{$id}.csv", [
            'Content-Type'  => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires'       => '0',
        ]);
    }
}
