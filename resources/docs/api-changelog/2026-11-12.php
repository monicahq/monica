<?php

declare(strict_types=1);

return [
    'date' => '2026-11-12',
    'entries' => [
        [
            'id' => 'possession-valuations',
            'kind' => 'Added',
            'group' => 'Possession valuations',
            'title' => 'Adds valuations of a possession',
            'summary' => 'Record what a possession is worth on a date, read the history, delete a valuation, and attach documents to it.',
            'why' => 'A single declared value cannot show how a worth moved or where a figure came from. A valuation is never changed once recorded. To correct one, record a new valuation.',
            'changes' => [
                ['field' => 'valuations', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions/{possession}/valuations', 'GET /vaults/{vault}/possessions/{possession}/valuations/{valuation}', 'POST /vaults/{vault}/possessions/{possession}/valuations', 'DELETE /vaults/{vault}/possessions/{possession}/valuations/{valuation}']],
                ['field' => 'file', 'change' => 'Added', 'endpoints' => ['POST /vaults/{vault}/possessions/{possession}/valuations/{valuation}/documents']],
                ['field' => 'file', 'change' => 'Added', 'endpoints' => ['DELETE /vaults/{vault}/possessions/{possession}/valuations/{valuation}/documents/{file}']],
            ],
            'example' => [
                'label' => 'POST valuations',
                'after' => '{ "type": "appraisal", "valued_on": "2026-03-14", "amount": 650, "currency": "usd" }',
            ],
        ],
        [
            'id' => 'possession-current-value',
            'kind' => 'Added',
            'group' => 'Possessions',
            'title' => 'Adds the current value of a possession',
            'summary' => 'Every possession carries current_value: the amount, the currency and the source, which is the latest valuation or else the declared value. It is null when neither exists.',
            'why' => 'Reading the worth of a possession needs no second request for its valuations. The field is read only, so nothing can be written to it.',
            'changes' => [
                ['field' => 'current_value', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions', 'GET /vaults/{vault}/possessions/{possession}', 'POST /vaults/{vault}/possessions', 'PUT /vaults/{vault}/possessions/{possession}']],
            ],
            'example' => [
                'label' => 'GET possessions/{possession}',
                'after' => '{ "attributes": { "name": "Dundie Award", "current_value": { "amount": "15.00", "currency": "usd", "source": "valuation" } } }',
            ],
        ],
        [
            'id' => 'possession-compact-list',
            'kind' => 'Added',
            'group' => 'Possessions',
            'title' => 'Adds a compact list of possessions',
            'summary' => 'List possessions accepts compact=true. Each possession then carries its name, its status, its type, its location and its current value, about a tenth of the full record.',
            'why' => 'A full possession is large. To go through everything a vault holds, ask for compact possessions with the largest page, then get one possession for the rest.',
            'changes' => [
                ['field' => 'compact', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions']],
            ],
        ],
        [
            'id' => 'possession-provenance',
            'kind' => 'Added',
            'group' => 'Possession provenance',
            'title' => 'Adds the provenance of a possession',
            'summary' => 'List, get, record, update and delete the moments in the history of a possession, such as where it was made or who owned it. Years before 1000 are accepted.',
            'why' => 'The history of a possession could only be written as a note. An event holds a date of a known precision (day, month, year, circa or unknown) and holds no money.',
            'changes' => [
                ['field' => 'provenance', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions/{possession}/provenance', 'GET /vaults/{vault}/possessions/{possession}/provenance/{event}', 'POST /vaults/{vault}/possessions/{possession}/provenance', 'PUT /vaults/{vault}/possessions/{possession}/provenance/{event}', 'DELETE /vaults/{vault}/possessions/{possession}/provenance/{event}']],
            ],
        ],
        [
            'id' => 'possession-location-stays',
            'kind' => 'Added',
            'group' => 'Possession locations',
            'title' => 'Adds the location history of a possession',
            'summary' => 'Move a possession to another location on a given day, and list, get, correct or delete its stays. The open stay is where the possession is now.',
            'why' => 'The location of a possession says where it is now, and its stays say where it has been. Moving it closes the current stay and opens a new one on the same day. A location that a stay refers to, current or past, cannot be deleted.',
            'changes' => [
                ['field' => 'location_id, moved_on', 'change' => 'Added', 'endpoints' => ['POST /vaults/{vault}/possessions/{possession}/location-stays']],
                ['field' => 'stays', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions/{possession}/location-stays', 'GET /vaults/{vault}/possessions/{possession}/location-stays/{stay}', 'PUT /vaults/{vault}/possessions/{possession}/location-stays/{stay}', 'DELETE /vaults/{vault}/possessions/{possession}/location-stays/{stay}']],
            ],
            'example' => [
                'label' => 'POST location-stays',
                'after' => '{ "location_id": "<location id>", "moved_on": "2024-06-04" }',
            ],
        ],
        [
            'id' => 'possession-loans',
            'kind' => 'Added',
            'group' => 'Possession loans',
            'title' => 'Adds loans of a possession',
            'summary' => 'Record a possession lent to a contact of its vault or borrowed from one, then list, get, change (including marking it as returned) and delete the loan. A possession has at most one active loan.',
            'why' => 'Who has a possession and when it is due back had no place in the API. A loan changes no owner, so it records no transaction. Name a contact with contact_id, or write a new one down with new_contact.',
            'changes' => [
                ['field' => 'loans', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions/{possession}/loans', 'GET /vaults/{vault}/possessions/{possession}/loans/{loan}', 'POST /vaults/{vault}/possessions/{possession}/loans', 'PUT /vaults/{vault}/possessions/{possession}/loans/{loan}', 'DELETE /vaults/{vault}/possessions/{possession}/loans/{loan}']],
            ],
            'example' => [
                'label' => 'POST loans',
                'after' => '{ "direction": "lent", "status": "active", "contact_id": "<contact id>", "started_on": "2026-11-01" }',
            ],
        ],
    ],
];
