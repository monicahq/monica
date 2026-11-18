<?php

declare(strict_types=1);

return [
    'date' => '2026-11-18',
    'entries' => [
        [
            'id' => 'agent-feedback',
            'kind' => 'Added',
            'group' => 'Agent feedback',
            'title' => 'Adds agent feedback',
            'summary' => 'An AI agent sends the administrators of the instance a report about a tool that failed, was missing or misled it, with what it tried and the fix it suggests. Administrators list the reports, read one, leave notes and move it between new, accepted, fixed and dismissed.',
            'why' => 'An agent that met a broken or missing tool had no way to say so, and the people who run the instance never heard about it.',
            'changes' => [
                ['field' => 'agent_feedback', 'change' => 'Added', 'endpoints' => ['POST /agent-feedback']],
                ['field' => 'agent_feedback_report', 'change' => 'Added', 'endpoints' => ['GET /admin/agent-feedback', 'GET /admin/agent-feedback/{agentFeedbackReport}', 'POST /admin/agent-feedback/{agentFeedbackReport}/notes', 'PUT /admin/agent-feedback/{agentFeedbackReport}/status']],
            ],
            'example' => [
                'label' => 'POST agent-feedback',
                'after' => '{ "kind": "bug", "severity": "blocks_task", "title": "Yearly reminders on 29 February are refused", "observed": "...", "expected": "...", "proposed_fix": "..." }',
            ],
        ],
        [
            'id' => 'possession-status-lent-removed',
            'kind' => 'Removed',
            'group' => 'Possessions',
            'title' => 'A possession no longer takes the status lent',
            'summary' => 'The status lent is gone. Whether a possession is lent out now comes from its loans: it is lent out while it has an active loan in the direction lent. Possessions stored as lent are now owned.',
            'why' => 'The status and the loans said two different things. Recording a loan never marked the possession as lent, and a possession could be marked lent with no loan at all.',
            'breaking' => true,
            'impact' => 'Sending status lent, or filtering by it, now answers 422. Record a loan of the possession instead, and read its active loan to know who has it.',
            'changes' => [
                ['field' => 'status lent', 'change' => 'Removed', 'endpoints' => ['GET /vaults/{vault}/possessions', 'POST /vaults/{vault}/possessions', 'PUT /vaults/{vault}/possessions/{possession}']],
            ],
        ],
        [
            'id' => 'possession-no-longer-held',
            'kind' => 'Changed',
            'group' => 'Possessions',
            'title' => 'A possession you no longer have cannot be moved or lent',
            'summary' => 'A possession that is sold, given away, thrown away, lost or stolen answers 422 on location_id when it is moved, and 422 on status when a planned or active loan of it is recorded. A returned loan is still accepted.',
            'why' => 'Something that has left your hands has no place to be moved to and cannot be lent out.',
            'breaking' => true,
            'impact' => 'An integration that moved or lent such a possession now gets 422. Change its status first, or record the loan as returned.',
            'changes' => [
                ['field' => 'location_id refused for a possession no longer held', 'change' => 'Changed', 'endpoints' => ['POST /vaults/{vault}/possessions/{possession}/location-stays', 'PUT /vaults/{vault}/possessions/{possession}']],
                ['field' => 'status planned or active refused for a possession no longer held', 'change' => 'Changed', 'endpoints' => ['POST /vaults/{vault}/possessions/{possession}/loans', 'PUT /vaults/{vault}/possessions/{possession}/loans/{loan}']],
            ],
        ],
        [
            'id' => 'merge-refused-during-import',
            'kind' => 'Changed',
            'group' => 'Duplicates and merges',
            'title' => 'No merge while an import writes into the vault',
            'summary' => 'Previewing or merging two contacts answers 409 while an import writes into their vault or rolls back what it wrote. Nothing is written. Merge them once the import has finished.',
            'why' => 'An import that rolls back deletes the contacts it wrote with everything attached to them, including what a merge had moved onto them, and the merge could then not be undone.',
            'breaking' => false,
            'changes' => [
                ['field' => '409 while an import writes into the vault', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/contacts/{contact}/merge/{other}', 'POST /vaults/{vault}/contacts/{contact}/merge/{other}']],
            ],
        ],
    ],
];
