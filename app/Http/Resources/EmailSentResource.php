<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EmailSent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * One email we sent, as the API hands it out.
 *
 * The four timestamps are all here, and so is the one word that reads them,
 * because a caller wanting to know whether an email arrived should not have to
 * work out that a bounce beats a delivery. That rule lives on the model, which
 * is also where the screen listing these reads it, so the two cannot disagree.
 *
 * The copy of the email is carried only when one email is asked for. A page of
 * twenty five emails is a list somebody is scanning, and putting the full body
 * of each one in it turns a list into a download.
 *
 * @mixin EmailSent
 */
class EmailSentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'email_sent',
            'id' => (string) $this->id,
            'attributes' => [
                'email_type' => $this->email_type,
                'subject' => $this->subject,
                'email_address' => $this->email_address,
                'body' => $this->when($request->routeIs('api.v1.emailsSent.show'), fn (): ?string => $this->body),
                'delivery_status' => $this->delivery_status->value,
                'sent_at' => $this->sent_at?->timestamp,
                'delivered_at' => $this->delivered_at?->timestamp,
                'bounced_at' => $this->bounced_at?->timestamp,
                'complained_at' => $this->complained_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.emailsSent.show', $this->id),
            ],
        ];
    }
}
