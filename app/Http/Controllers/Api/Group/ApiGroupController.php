<?php

namespace App\Http\Controllers\Api\Group;

use App\Models\Group\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Services\Group\Group\CreateGroup;
use App\Services\Group\Group\UpdateGroup;
use App\Services\Group\Group\DestroyGroup;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use App\Services\Group\Group\AddContactToGroup;
use App\Http\Resources\Group\Group as GroupResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiGroupController extends ApiController
{
    /**
     * Get the list of groups.
     *
     * @param Request $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $groups = auth()->user()->account->groups()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return GroupResource::collection($groups);
    }

    /**
     * Get the detail of a given group.
     *
     * @param Request $request
     * @param int $groupId
     * @return GroupResource|JsonResponse
     */
    public function show(Request $request, int $groupId)
    {
        try {
            $group = Group::where('account_id', auth()->user()->account_id)
                ->where('id', $groupId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new GroupResource($group);
    }

    /**
     * Store the group.
     *
     * @param Request $request
     * @return GroupResource|JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $group = app(CreateGroup::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account->id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new GroupResource($group);
    }

    /**
     * Update the group.
     *
     * @param Request $request
     * @param int $groupId
     * @return GroupResource|JsonResponse
     */
    public function update(Request $request, int $groupId)
    {
        try {
            $group = app(UpdateGroup::class)->execute(
                $request->except(['account_id', 'group_id'])
                    +
                    [
                        'account_id' => auth()->user()->account->id,
                        'group_id' => $groupId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new GroupResource($group);
    }

    /**
     * Delete a group.
     *
     * @param Request $request
     * @param int $groupId
     * @return JsonResponse
     */
    public function destroy(Request $request, int $groupId)
    {
        try {
            app(DestroyGroup::class)->execute([
                'account_id' => auth()->user()->account->id,
                'group_id' => $groupId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $groupId);
    }

    /**
     * Attach or detach a set of contacts to a group.
     *
     * @param Request $request
     * @param int $groupId
     * @return GroupResource|JsonResponse
     */
    public function attachContacts(Request $request, int $groupId)
    {
        $group = [];

        try {
            foreach ($request->input('contacts') as $contactId) {
                $group = app(AddContactToGroup::class)->execute([
                    'account_id' => auth()->user()->account->id,
                    'group_id' => $groupId,
                    'contact_id' => $contactId,
                ]);
            }
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new GroupResource($group);
    }
}
