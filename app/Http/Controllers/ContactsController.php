<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Helpers\GendersHelper;
use App\Models\Contact\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\Contact\Contact\CreateContact;

class ContactsController extends Controller
{
    /**
     * Display all the contacts in the account.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $account = Auth::user()->account;

        // contacts
        $contacts = $account->contacts()->real()->active()
            ->with('tags')
            ->paginate(30);

        $contactsCollection = collect([]);
        foreach ($contacts as $contact) {
            $tags = collect([]);
            foreach ($contact->tags as $tag) {
                $tags->push([
                    'id' => $tag->id,
                    'name' => $tag->name,
                ]);
            }

            $contactsCollection->push([
                'id' => $contact->id,
                'name' => $contact->name,
                'avatar' => $contact->getAvatarURL(),
                'tags' => $tags->toArray(),
                'url' => route('people.show', ['contact' => $contact]),
            ]);
        }

        // all tags in the account
        $tagsInAccount = $account->tags()->with('contacts')->get();
        //$tagsInAccount = DB::table('tags')->where('account_id', $account->id)->get();
        $tagsCollection = collect([]);
        foreach ($tagsInAccount as $tag) {
            $tagsCollection->push([
                'id' => $tag->id,
                'name' => $tag->name,
                'number' => $tag->contacts->count(),
                'url' => route('people.tag', ['tag' => $tag]),
            ]);
        }

        // number of archived contacts
        $numberOfArchivedContacts = $account->contacts()->notActive()->count();

        return Inertia::render('Contact/Index', [
            'contacts' => $contactsCollection,
            'tags' => $tagsCollection,
            'numberOfArchivedContacts' => $numberOfArchivedContacts,
            'urls' => [
                'cta' => route('people.new'),
            ],
            'paginator' => [
                'hasMorePages' => $contacts->hasMorePages(),
                'currentPage' => $contacts->currentPage(),
                'nextPageUrl' => $contacts->nextPageUrl(),
                'previousPageUrl' => $contacts->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Display the Add contact form.
     *
     * @return Response
     */
    public function new()
    {
        return Inertia::render('Contact/New', [
            'genders' => GendersHelper::getGendersInput(),
        ]);
    }

    /**
     * Display the profile of the contact.
     *
     * @param Contact $contact
     *
     * @return Response
     */
    public function show(Contact $contact)
    {
        $contactObject = [
            'name' => $contact->name,
            'avatar' => $contact->getAvatarURL(),
            'age' => ($contact->birthdate) ? $contact->birthdate->getAge() : null,
            'description' => $contact->description,
        ];

        return Inertia::render('Contact/Show', [
            'contact' => $contactObject,
        ]);
    }

    /**
     * Store the contact.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $contact = app(CreateContact::class)->execute([
            'account_id' => auth()->user()->account_id,
            'first_name' => $request->input('firstname'),
            'last_name' => $request->input('lastname', null),
            'nickname' => $request->input('nickname', null),
            'gender_id' => $request->input('gender'),
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ]);

        return response()->json([
            'data' => [
                'url' => route('people.show', ['contact' => $contact]),
            ],
        ], 201);
    }
}
