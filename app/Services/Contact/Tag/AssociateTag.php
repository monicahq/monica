<?php

namespace App\Services\Contact\Tag;

use App\Models\Contact\Tag;
use App\Helpers\LocaleHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class AssociateTag extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_id' => 'required|integer',
            'name' => 'required|string',
        ];
    }

    /**
     * Associate a tag to a contact.
     *
     * @param array $data
     * @return Tag
     */
    public function execute(array $data) : Tag
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
                            ->findOrFail($data['contact_id']);

        // check if the tag already exists in the account
        $tag = $this->tagExistOrCreate($data);

        // associate the tag to the contact
        $this->associateToContact($tag, $contact);

        return $tag;
    }

    /**
     * Check if the tag already exists in the account.
     * If it does, returns it.
     * If it doesn't, create it.
     *
     * @return Tag
     */
    private function tagExistOrCreate(array $data) : Tag
    {
        $tag = Tag::where('name', $data['name'])
                ->where('account_id', $data['account_id'])
                ->first();

        if (! $tag) {
            return $this->createTag($data);
        }

        return $tag;
    }

    /**
     * Creates the tag.
     *
     * @return Tag
     */
    private function createTag(array $data) : Tag
    {
        $array = [
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'name_slug' => str_slug($data['name'], '-', LocaleHelper::getLang()),
        ];

        if (empty($array['name_slug'])) {
            $array['name_slug'] = htmlentities($data['name']);
        }

        return Tag::create($array);
    }

    /**
     * Associate the tag to the contact.
     *
     * @return void
     */
    private function associateToContact(Tag $tag, Contact $contact)
    {
        // make sure the tag is not associated with the contact already
        $contact->tags()->detach($tag->id);

        $contact->tags()->syncWithoutDetaching([
            $tag->id => [
                'account_id' => $contact->account_id,
            ],
        ]);
    }
}
