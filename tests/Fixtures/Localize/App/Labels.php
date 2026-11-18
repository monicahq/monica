<?php

declare(strict_types=1);

namespace Tests\Fixtures\Localize\App;

class Labels
{
    /** @return list<mixed> */
    public function all(int $count): array
    {
        // __('Why this is empty.')
        return [
            __('Preferences'),
            __('Delete :name?', ['name' => 'Dwight Schrute']),
            trans_choice(':count person|:count people', $count),
            trans_choice('[0,*]Show :count more', $count),
        ];
    }
}
