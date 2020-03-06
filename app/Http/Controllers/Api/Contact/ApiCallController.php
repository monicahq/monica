<?php

namespace App\Http\Controllers\Api\Contact;

use App\Models\Contact\Call;
use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use App\Services\Contact\Call\CreateCall;
use App\Services\Contact\Call\UpdateCall;
use App\Services\Contact\Call\DestroyCall;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Call\Call as CallResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiCallController extends ApiController
{
    /**
     * Get the list of calls.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $calls = auth()->user()->account->calls()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return CallResource::collection($calls)->additional(['meta' => [
            'statistics' => AccountHelper::getYearlyCallStatistics(auth()->user()->account),
        ]]);
    }

    /**
     * Get the detail of a given call.
     *
     * @param Request $request
     *
     * @return CallResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $callId)
    {
        try {
            $call = Call::where('account_id', auth()->user()->account_id)
                ->where('id', $callId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CallResource($call);
    }

    /**
     * Store the call.
     *
     * @param Request $request
     *
     * @return CallResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $call = app(CreateCall::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new CallResource($call);
    }

    /**
     * Update a call.
     *
     * @param Request $request
     * @param int $callId
     *
     * @return CallResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $callId)
    {
        try {
            $call = app(UpdateCall::class)->execute(
                $request->except(['account_id', 'call_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'call_id' => $callId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new CallResource($call);
    }

    /**
     * Delete a call.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $callId)
    {
        try {
            app(DestroyCall::class)->execute([
                'account_id' => auth()->user()->account_id,
                'call_id' => $callId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $callId);
    }

    /**
     * Get the list of calls for a given contact.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function calls(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $calls = $contact->calls()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());

        return CallResource::collection($calls)->additional(['meta' => [
            'statistics' => AccountHelper::getYearlyCallStatistics(auth()->user()->account),
        ]]);
    }
}
