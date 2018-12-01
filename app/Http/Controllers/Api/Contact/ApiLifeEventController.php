/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use App\Models\Contact\LifeEvent;
use App\Http\Controllers\Api\ApiController;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\LifeEvent\CreateLifeEvent;
use App\Services\Contact\LifeEvent\UpdateLifeEvent;
use App\Services\Contact\LifeEvent\DestroyLifeEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\LifeEvent\LifeEvent as LifeEventResource;

class ApiLifeEventController extends ApiController
{
    /**
     * Get the list of life events.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $lifeEvents = auth()->user()->account->lifeEvents()
            ->orderBy($this->sort, $this->sortDirection)
            ->paginate($this->getLimitPerPage());

        return LifeEventResource::collection($lifeEvents);
    }

    /**
     * Get the detail of a given life event.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $lifeEventId)
    {
        try {
            $lifeEvent = LifeEvent::where('account_id', auth()->user()->account_id)
                                    ->findOrFail($lifeEventId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new LifeEventResource($lifeEvent);
    }

    /**
     * Store the life event.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $lifeEvent = (new CreateLifeEvent)->execute(
                $request->all()
                +
                [
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        }

        return new LifeEventResource($lifeEvent);
    }

    /**
     * Update the life event.
     *
     * @param  Request $request
     * @param  int $lifeEventId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $lifeEventId)
    {
        try {
            $lifeEvent = (new UpdateLifeEvent)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                    'life_event_id' => $lifeEventId,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        }

        return new LifeEventResource($lifeEvent);
    }

    /**
     * Destroy the life event.
     *
     * @param  Request $request
     * @param  int $lifeEventId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $lifeEventId)
    {
        try {
            (new DestroyLifeEvent)->execute([
                'account_id' => auth()->user()->account->id,
                'life_event_id' => $lifeEventId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return $this->respondObjectDeleted((int) $lifeEventId);
    }
}
