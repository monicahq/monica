<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\RestoreLifeEvent;
use App\Enums\ApiKeyScopeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventResource;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class LifeEventRestorationController extends Controller
{
    public function update(Request $request, string $vault, string $lifeEvent): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $lifeEvent = $this->lifeEvent($vault, $lifeEvent);

        $subject = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participation): mixed => $participation->participant)
            ->first(fn (mixed $person): bool => $person instanceof Contact);

        if (! $subject instanceof Contact) {
            throw ValidationException::withMessages(['participants' => __('Nobody who took part in this life event is left, so it cannot be changed here.')]);
        }

        new RestoreLifeEvent(
            user: $request->user(),
            vault: $vault,
            contact: $subject,
            lifeEvent: $lifeEvent,
        )->execute();

        $resource = new LifeEventResource($this->lifeEvent($vault, $lifeEvent->uuid));
        $resource->withSensitive = $this->reachesSensitiveFields($request->user());

        return $resource->response()->setStatusCode(200);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function lifeEvent(Vault $vault, string $address): LifeEvent
    {
        return LifeEvent::query()
            ->withTrashed()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->with(LifeEventResource::RELATIONS)
            ->firstOrFail();
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }
}
