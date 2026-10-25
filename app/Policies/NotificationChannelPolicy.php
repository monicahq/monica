<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotificationChannelPolicy
{
    public function sendProofCode(User $user, NotificationChannel $channel): Response
    {
        if (! $channel->channel->provesSilently()) {
            return Response::deny(__('This channel says for itself whether a destination is wrong, so it has nothing to prove.'));
        }

        if (! $channel->channel->isConfiguredOnTheInstance()) {
            return Response::deny(__('This server has not been set up to send on this channel, so no code can be sent.'));
        }

        if ($channel->proof_sent_count >= (int) config('monica.notification_proof_maximum_sends')) {
            return Response::deny(__('No more codes will be sent to this number. Remove it to type a number again.'));
        }

        $secondsLeft = $this->secondsBeforeAnotherSend($channel);

        if ($secondsLeft > 0) {
            return Response::deny(trans_choice('You can ask for another code in :count second.|You can ask for another code in :count seconds.', $secondsLeft));
        }

        return Response::allow();
    }

    private function secondsBeforeAnotherSend(NotificationChannel $channel): int
    {
        if ($channel->proof_last_sent_at === null) {
            return 0;
        }

        $wait = (int) config('monica.notification_proof_resend_seconds');

        return (int) ceil($wait - $channel->proof_last_sent_at->diffInSeconds(now()));
    }
}
