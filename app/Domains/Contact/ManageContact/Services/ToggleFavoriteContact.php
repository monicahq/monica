<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ToggleFavoriteContact extends BaseService implements ServiceInterface
{
    private array $data;

    private bool $isFavorite;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Toggle the favorite state of a contact for the given user.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();

        $this->toggle();
        $this->updateLastEditedDate();
        $this->createFeedItem();

        return $this->contact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function toggle(): void
    {
        $contact = [
            'contact_id' => $this->data['contact_id'],
            'vault_id' => $this->data['vault_id'],
            'user_id' => $this->data['author_id'],
        ];

        $exists = DB::table('contact_vault_user')
            ->where($contact)
            ->first();

        if ($exists) {
            $this->isFavorite = $exists->is_favorite;

            DB::table('contact_vault_user')
                ->where($contact)
                ->update(['is_favorite' => ! $this->isFavorite]);
        } else {
            $this->isFavorite = true;

            DB::table('contact_vault_user')->insert($contact + [
                'is_favorite' => true,
                'number_of_views' => 1,
            ]);
        }
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function createFeedItem(): void
    {
        ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => $this->isFavorite ? ContactFeedItem::ACTION_UNFAVORITED_CONTACT : ContactFeedItem::ACTION_FAVORITED_CONTACT,
        ]);
    }
}
