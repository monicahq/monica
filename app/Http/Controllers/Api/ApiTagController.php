<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact\Tag;
use Illuminate\Http\Request;
use App\Services\Contact\Tag\CreateTag;
use App\Services\Contact\Tag\UpdateTag;
use Illuminate\Database\QueryException;
use App\Services\Contact\Tag\DestroyTag;
use App\Http\Resources\Tag\Tag as TagResource;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiTagController extends ApiController
{
    /**
     * Get the list of the contacts.
     * We will only retrieve the contacts that are "real", not the partials
     * ones.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $tags = auth()->user()->account->tags()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return TagResource::collection($tags);
    }

    /**
     * Get the detail of a given tag.
     *
     * @param Request $request
     *
     * @return TagResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $tag = Tag::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new TagResource($tag);
    }

    /**
     * Store the tag.
     *
     * @param Request $request
     *
     * @return TagResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $tag = app(CreateTag::class)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return new TagResource($tag);
    }

    /**
     * Update the tag.
     *
     * @param Request $request
     *
     * @return TagResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $tag = app(UpdateTag::class)->execute(
                $request->all()
                    +
                    [
                    'tag_id' => $id,
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return new TagResource($tag);
    }

    /**
     * Delete a tag.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            app(DestroyTag::class)->execute([
                'tag_id' => $id,
                'account_id' => auth()->user()->account->id,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return $this->respondObjectDeleted($id);
    }
}
