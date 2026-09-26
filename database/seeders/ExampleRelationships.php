<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RelationshipStatusEnum;
use App\Enums\RelationshipTypeEnum;

/**
 * Who is who to whom in the example account, as the show leaves it at the finale. Person A holds
 * the first role of the type (the parent, the grandparent, the aunt), and a date is the day the
 * episode aired.
 */
class ExampleRelationships
{
    public const string NEIGHBOUR = 'neighbour';

    private const string MONICA = 'Monica Geller';

    private const string RACHEL = 'Rachel Green';

    private const string ROSS = 'Ross Geller';

    private const string CHANDLER = 'Chandler Bing';

    private const string JOEY = 'Joey Tribbiani';

    private const string PHOEBE = 'Phoebe Buffay';

    private const string MIKE = 'Mike Hannigan';

    private const string GUNTHER = 'Gunther';

    private const string JANICE = 'Janice Litman';

    private const string RICHARD = 'Richard Burke';

    private const string EMILY = 'Emily Waltham';

    private const string CAROL = 'Carol Willick';

    private const string SUSAN = 'Susan Bunch';

    private const string BEN = 'Ben Geller';

    private const string EMMA = 'Emma Geller Green';

    private const string JACK = 'Jack Geller';

    private const string JUDY = 'Judy Geller';

    private const string HECKLES = 'Mr. Heckles';

    private const string JACK_BING = 'Jack Bing';

    private const string ERICA = 'Erica Bing';

    private const string FRANK = 'Frank Buffay Jr.';

    private const string URSULA = 'Ursula Buffay';

    private const string BARRY = 'Barry Farber';

    private const string NORA = 'Nora Bing';

    private const string HELENA = 'Helena Bing';

    /** @return list<array{type: string, a: string, b: string, status: RelationshipStatusEnum, started: ?string, ended: ?string}> */
    public function all(): array
    {
        $parent = RelationshipTypeEnum::ParentChild->value;
        $sibling = RelationshipTypeEnum::Sibling->value;
        $grandparent = RelationshipTypeEnum::GrandparentGrandchild->value;
        $aunt = RelationshipTypeEnum::AuntUncleNieceNephew->value;
        $cousin = RelationshipTypeEnum::Cousin->value;
        $spouse = RelationshipTypeEnum::Spouse->value;
        $fiance = RelationshipTypeEnum::Fiance->value;
        $partner = RelationshipTypeEnum::Partner->value;
        $friend = RelationshipTypeEnum::Friend->value;
        $colleague = RelationshipTypeEnum::Colleague->value;
        $ended = RelationshipStatusEnum::Ended;

        $links = [
            self::link($parent, self::JACK, self::MONICA),
            self::link($parent, self::JACK, self::ROSS),
            self::link($parent, self::JUDY, self::MONICA),
            self::link($parent, self::JUDY, self::ROSS),
            self::link($parent, self::ROSS, self::BEN),
            self::link($parent, self::CAROL, self::BEN),
            self::link($parent, self::SUSAN, self::BEN),
            self::link($parent, self::ROSS, self::EMMA),
            self::link($parent, self::RACHEL, self::EMMA),
            self::link($parent, self::MONICA, self::JACK_BING),
            self::link($parent, self::MONICA, self::ERICA),
            self::link($parent, self::CHANDLER, self::JACK_BING),
            self::link($parent, self::CHANDLER, self::ERICA),
            self::link($parent, self::NORA, self::CHANDLER),
            self::link($parent, self::HELENA, self::CHANDLER),

            self::link($sibling, self::MONICA, self::ROSS),
            self::link($sibling, self::PHOEBE, self::URSULA),
            self::link($sibling, self::PHOEBE, self::FRANK),
            self::link($sibling, self::JACK_BING, self::ERICA),
            self::link($sibling, self::BEN, self::EMMA),

            self::link($grandparent, self::JACK, self::BEN),
            self::link($grandparent, self::JACK, self::EMMA),
            self::link($grandparent, self::JACK, self::JACK_BING),
            self::link($grandparent, self::JACK, self::ERICA),
            self::link($grandparent, self::JUDY, self::BEN),
            self::link($grandparent, self::JUDY, self::EMMA),
            self::link($grandparent, self::JUDY, self::JACK_BING),
            self::link($grandparent, self::JUDY, self::ERICA),
            self::link($grandparent, self::NORA, self::JACK_BING),
            self::link($grandparent, self::NORA, self::ERICA),
            self::link($grandparent, self::HELENA, self::JACK_BING),
            self::link($grandparent, self::HELENA, self::ERICA),

            self::link($aunt, self::MONICA, self::BEN),
            self::link($aunt, self::MONICA, self::EMMA),
            self::link($aunt, self::CHANDLER, self::BEN),
            self::link($aunt, self::CHANDLER, self::EMMA),
            self::link($aunt, self::ROSS, self::JACK_BING),
            self::link($aunt, self::ROSS, self::ERICA),

            self::link($cousin, self::BEN, self::JACK_BING),
            self::link($cousin, self::BEN, self::ERICA),
            self::link($cousin, self::EMMA, self::JACK_BING),
            self::link($cousin, self::EMMA, self::ERICA),

            self::link($spouse, self::MONICA, self::CHANDLER, started: '2001-05-17'),
            self::link($spouse, self::JACK, self::JUDY),
            self::link($spouse, self::PHOEBE, self::MIKE, started: '2004-02-12'),
            self::link($spouse, self::CAROL, self::SUSAN, started: '1996-01-18'),
            self::link($spouse, self::ROSS, self::CAROL, $ended),
            self::link($spouse, self::ROSS, self::EMILY, $ended, '1998-05-07', '1998-11-05'),
            self::link($spouse, self::ROSS, self::RACHEL, $ended, '1999-05-20', '1999-11-04'),
            self::link($spouse, self::NORA, self::HELENA, $ended, ended: '1978-11-23'),

            self::link($fiance, self::RACHEL, self::BARRY, $ended, ended: '1994-09-22'),

            self::link($partner, self::ROSS, self::RACHEL, $ended, '1995-11-09', '1997-02-20'),
            self::link($partner, self::ROSS, self::RACHEL, started: '2004-05-06'),
            self::link($partner, self::MONICA, self::RICHARD, $ended, '1996-02-08', '1996-05-16'),
            self::link($partner, self::CHANDLER, self::JANICE, $ended, ended: '1997-01-16'),
            self::link($partner, self::JOEY, self::RACHEL, $ended, '2003-05-15', '2003-10-09'),
            self::link($partner, self::PHOEBE, self::MIKE, $ended, '2002-10-10', '2004-02-12'),

            self::link($friend, self::MIKE, self::MONICA),
            self::link($friend, self::MIKE, self::RACHEL),
            self::link($friend, self::MIKE, self::ROSS),
            self::link($friend, self::MIKE, self::CHANDLER),
            self::link($friend, self::MIKE, self::JOEY),
            self::link($friend, self::JACK, self::RICHARD),

            self::link($colleague, self::RACHEL, self::GUNTHER, $ended, '1994-09-29', '1996-12-12'),

            self::link(self::NEIGHBOUR, self::HECKLES, self::MONICA, $ended, ended: '1995-10-05'),
            self::link(self::NEIGHBOUR, self::HECKLES, self::RACHEL, $ended, ended: '1995-10-05'),
            self::link(self::NEIGHBOUR, self::HECKLES, self::CHANDLER, $ended, ended: '1995-10-05'),
            self::link(self::NEIGHBOUR, self::HECKLES, self::JOEY, $ended, ended: '1995-10-05'),
        ];

        $six = [self::MONICA, self::RACHEL, self::ROSS, self::CHANDLER, self::JOEY, self::PHOEBE];
        $describedElsewhere = [[self::MONICA, self::ROSS], [self::MONICA, self::CHANDLER]];

        foreach ($six as $index => $first) {
            foreach (array_slice($six, $index + 1) as $second) {
                if (in_array([$first, $second], $describedElsewhere, true)) {
                    continue;
                }

                $links[] = self::link($friend, $first, $second);
            }
        }

        return $links;
    }

    /** @return array{type: string, a: string, b: string, status: RelationshipStatusEnum, started: ?string, ended: ?string} */
    private static function link(
        string $type,
        string $a,
        string $b,
        RelationshipStatusEnum $status = RelationshipStatusEnum::Current,
        ?string $started = null,
        ?string $ended = null,
    ): array {
        return ['type' => $type, 'a' => $a, 'b' => $b, 'status' => $status, 'started' => $started, 'ended' => $ended];
    }
}
