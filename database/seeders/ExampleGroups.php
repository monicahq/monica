<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * The groups of the example account: the friends who meet at the coffee house, and the two
 * apartments across the hall from each other. A date is the day the episode aired, and a date left
 * empty is one the show never gives.
 */
class ExampleGroups
{
    private const string MONICA = 'Monica Geller';

    private const string RACHEL = 'Rachel Green';

    private const string ROSS = 'Ross Geller';

    private const string CHANDLER = 'Chandler Bing';

    private const string JOEY = 'Joey Tribbiani';

    private const string PHOEBE = 'Phoebe Buffay';

    private const string GUNTHER = 'Gunther';

    private const string PILOT = '1994-09-22';

    private const string THE_ONE_ON_THE_LAST_NIGHT = '1999-11-04';

    private const string THE_LAST_ONE = '2004-05-06';

    /** @return list<array{name: string, description: string, started: ?string, ended: ?string, members: list<array{name: string, role: ?string, joined: ?string, left: ?string}>}> */
    public function all(): array
    {
        return [
            [
                'name' => 'Central Perk regulars',
                'description' => 'The six of them on the orange couch, and the man behind the counter.',
                'started' => self::PILOT,
                'ended' => null,
                'members' => [
                    ['name' => self::MONICA, 'role' => null, 'joined' => self::PILOT, 'left' => null],
                    ['name' => self::RACHEL, 'role' => null, 'joined' => self::PILOT, 'left' => null],
                    ['name' => self::ROSS, 'role' => null, 'joined' => self::PILOT, 'left' => null],
                    ['name' => self::CHANDLER, 'role' => null, 'joined' => self::PILOT, 'left' => null],
                    ['name' => self::JOEY, 'role' => null, 'joined' => self::PILOT, 'left' => null],
                    ['name' => self::PHOEBE, 'role' => null, 'joined' => self::PILOT, 'left' => null],
                    ['name' => self::GUNTHER, 'role' => 'Manager', 'joined' => null, 'left' => null],
                ],
            ],
            [
                'name' => 'Apartment 20',
                'description' => 'The purple apartment on Bedford Street, rented under Monica’s grandmother’s name.',
                'started' => null,
                'ended' => self::THE_LAST_ONE,
                'members' => [
                    ['name' => self::MONICA, 'role' => 'Leaseholder', 'joined' => null, 'left' => self::THE_LAST_ONE],
                    ['name' => self::RACHEL, 'role' => 'Roommate', 'joined' => self::PILOT, 'left' => self::THE_ONE_ON_THE_LAST_NIGHT],
                    ['name' => self::CHANDLER, 'role' => null, 'joined' => self::THE_ONE_ON_THE_LAST_NIGHT, 'left' => self::THE_LAST_ONE],
                ],
            ],
            [
                'name' => 'Apartment 19',
                'description' => 'Across the hall: the foosball table, the recliners and the duck and the chick.',
                'started' => null,
                'ended' => null,
                'members' => [
                    ['name' => self::JOEY, 'role' => null, 'joined' => null, 'left' => null],
                    ['name' => self::CHANDLER, 'role' => 'Roommate', 'joined' => null, 'left' => self::THE_ONE_ON_THE_LAST_NIGHT],
                ],
            ],
        ];
    }
}
