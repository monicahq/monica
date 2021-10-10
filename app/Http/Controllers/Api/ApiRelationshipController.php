<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Relationship\CreateRelationship;
use App\Services\Contact\Relationship\UpdateRelationship;
use App\Services\Contact\Relationship\DestroyRelationship;
use App\Http\Resources\Relationship\Relationship as RelationshipResource;

class ApiRelationshipController extends ApiController
{
    /**
     * Get all of relationships of a contact.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $contactId)
    {
        try {
            $relationships = Relationship::where('account_id', auth()->user()->account_id)
                            ->where('contact_is', $contactId)
                            ->get();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return RelationshipResource::collection($relationships);
    }

    /**
     * Get the detail of a given relationship.
     *
     * @param  Request  $request
     * @return RelationshipResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $relationship = Relationship::where('account_id', auth()->user()->account_id)
                            ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new RelationshipResource($relationship);
    }

    /**
     * Create a new relationship.
     *
     * @param  Request  $request
     * @return RelationshipResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $relationship = app(CreateRelationship::class)->execute([
                'account_id' => auth()->user()->account_id,
                'contact_is' => $request->input('contact_is'),
                'of_contact' => $request->input('of_contact'),
                'relationship_type_id' => $request->input('relationship_type_id'),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return new RelationshipResource($relationship);
    }

    /**
     * Update an existing relationship.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|RelationshipResource
     */
    public function update(Request $request, $relationshipId)
    {
        try {
            $relationship = app(UpdateRelationship::class)->execute([
                'account_id' => auth()->user()->account_id,
                'relationship_id' => $relationshipId,
                'relationship_type_id' => $request->input('relationship_type_id'),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        $relationship->refresh();

        return new RelationshipResource($relationship);
    }

    /**
     * Delete a relationship.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $relationshipId)
    {
        try {
            app(DestroyRelationship::class)->execute([
                'account_id' => auth()->user()->account_id,
                'relationship_id' => $relationshipId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return $this->respondObjectDeleted($relationshipId);
    }
}
