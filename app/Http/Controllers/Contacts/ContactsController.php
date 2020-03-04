<?php

namespace App\Http\Controllers\Contacts;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Helpers\GenderHelper;
use App\Models\Contact\Contact;
use App\Helpers\PaginatorHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Jobs\UpdateLastConsultedDate;
use App\ViewHelpers\ContactViewHelper;
use App\ViewHelpers\ContactListViewHelper;
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
        $allContactsInAccount = DB::table('contacts')
            ->where('account_id', $account->id)
            ->where('is_active', 1)
            ->count();
        $contactsCollection = ContactListViewHelper::getListOfContacts($contacts);

        // all tags in the account
        $tagsCollection = ContactListViewHelper::getListOfTags($account);

        // number of archived contacts
        $numberOfArchivedContacts = $account->contacts()->notActive()->count();

        return Inertia::render('Contact/Index', [
            'contacts' => $contactsCollection,
            'count' => $allContactsInAccount,
            'tags' => $tagsCollection,
            'numberOfArchivedContacts' => $numberOfArchivedContacts,
            'urls' => [
                'cta' => route('people.new'),
            ],
            'paginator' => PaginatorHelper::getData($contacts),
        ]);
    }

    /**
     * Display the Add contact form.
     *
     * @return Response
     */
    public function new(): Response
    {
        return Inertia::render('Contact/New', [
            'genders' => GenderHelper::getGendersInput(),
        ]);
    }

    /**
     * Display the profile of the contact.
     *
     * @param Contact $contact
     * @return Response
     */
    public function show(Contact $contact): Response
    {
        // audit logs
        $logs = $contact->logs()->with('author')->latest()->paginate(10);

        $contactObject = [
            'hash' => $contact->hashId(),
            'name' => $contact->name,
            'avatar' => $contact->getAvatarURL(),
            'age' => ($contact->birthdate) ? $contact->birthdate->getAge() : null,
            'description' => $contact->description,
            'work' => [
                'title' => $contact->job,
                'company' => $contact->company,
                'description' => ContactViewHelper::getWorkInformation($contact),
            ],
            'audit_logs' => [
                'content' => ContactViewHelper::getListOfAuditLogs($logs),
                'paginator' => PaginatorHelper::getData($logs),
            ],
            'addresses' => ContactViewHelper::getAddresses($contact),
        ];

        UpdateLastConsultedDate::dispatch($contact);

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
    public function create(Request $request): JsonResponse
    {
        $contact = app(CreateContact::class)->execute([
            'account_id' => auth()->user()->account_id,
            'author_id' => auth()->user()->id,
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
