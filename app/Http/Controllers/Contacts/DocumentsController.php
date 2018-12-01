<?php

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

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use App\Http\Controllers\Controller;
use App\Services\Contact\Document\UploadDocument;
use App\Services\Contact\Document\DestroyDocument;
use App\Http\Resources\Document\Document as DocumentResource;

class DocumentsController extends Controller
{
    /**
     * Display the list of documents.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Contact $contact)
    {
        $documents = $contact->documents()->get();

        return DocumentResource::collection($documents);
    }

    /**
     * Store the document.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Contact $contact)
    {
        return (new UploadDocument)->execute([
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'document' => $request->document,
        ]);
    }

    /**
     * Delete the document.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Document $document
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Contact $contact, Document $document)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'document_id' => $document->id,
        ];

        try {
            (new DestroyDocument)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }
    }
}
