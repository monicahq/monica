<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * The Knowledge of the example account: what Monica writes down so she never has to ask twice, in
 * eight folders. The notes use every kind of formatting a note can show, and the lists name their own
 * columns, the first two replacing the "Name" and "Note" a new list starts with. A null cell is blank.
 */
class ExampleKnowledge
{
    /** @return list<array{name: string, notes: list<array{title: string, body: string}>, lists: list<array{title: string, description: string, columns: list<string>, items: list<list<?string>>}>}> */
    public function all(): array
    {
        return [
            [
                'name' => 'Personal',
                'notes' => [
                    [
                        'title' => 'About me',
                        'body' => <<<'MARKDOWN'
                            ## The short version

                            Chef. Big sister. The one who hosts Thanksgiving, every year, whether anybody asked or not.

                            ## Things people should know

                            - I fold the towels a certain way. There is a **reason**.
                            - I was not a cheerleader, I was a *very enthusiastic* member of the audience.
                            - Apartment 20 is rent controlled, and that is nobody's business.
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Life goals',
                        'body' => <<<'MARKDOWN'
                            ## Work

                            - Run my own kitchen, with my name on the menu
                            - Get a review in the *Times* that does not mention the word "quaint"

                            ## Home

                            - A house with a yard big enough for a swing set
                            - A kitchen with two ovens, so Thanksgiving stops being a timing problem
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Favorite things',
                        'body' => <<<'MARKDOWN'
                            - Label makers
                            - The smell of a new sponge
                            - A clean oven at the end of service
                            - Winning. At anything. Including the game where you guess what is in the box.
                            MARKDOWN,
                    ],
                ],
                'lists' => [
                    [
                        'title' => 'Personal preferences',
                        'description' => 'What I like, so nobody has to guess at my birthday.',
                        'columns' => ['Preference', 'Choice', 'Note'],
                        'items' => [
                            ['Coffee', 'Black, no sugar', 'Not the one Gunther makes after six'],
                            ['Flowers', 'White tulips', null],
                            ['Restaurant seat', 'Facing the kitchen', 'I like to watch the line'],
                            ['Music while cooking', 'Motown', 'Loud'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Travel',
                'notes' => [
                    [
                        'title' => 'Japan trip memories',
                        'body' => <<<'MARKDOWN'
                            ## Tokyo

                            The fish market at five in the morning. I have never seen tuna treated with such respect.

                            ## Kyoto

                            A kaiseki dinner of eleven courses. I took notes on every one of them, and the chef noticed.

                            > Next time, stay longer in Osaka and eat everything.
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Places to visit',
                        'body' => <<<'MARKDOWN'
                            - [San Sebastián](https://en.wikipedia.org/wiki/San_Sebasti%C3%A1n), for the pintxos bars
                            - [Lyon](https://en.wikipedia.org/wiki/Lyon), for the bouchons
                            - [Oaxaca](https://en.wikipedia.org/wiki/Oaxaca_City), for the seven moles
                            - Barbados, again, but this time without a paleontology conference
                            MARKDOWN,
                    ],
                ],
                'lists' => [
                    [
                        'title' => 'Countries visited',
                        'description' => 'Every country I have been to, and what I ate there.',
                        'columns' => ['Country', 'Year', 'Best meal'],
                        'items' => [
                            ['England', '1998', 'A Sunday roast in a pub near the wedding venue'],
                            ['Barbados', '2002', 'Flying fish and cou-cou'],
                            ['Japan', '2005', 'Kaiseki in Kyoto'],
                            ['Italy', '2008', null],
                        ],
                    ],
                    [
                        'title' => 'Travel wishlist',
                        'description' => 'Where I want to go next, roughly in order.',
                        'columns' => ['Destination', 'Why', 'Best season'],
                        'items' => [
                            ['Spain', 'Pintxos in San Sebastián', 'Autumn'],
                            ['France', 'Cooking class in Lyon', 'Spring'],
                            ['Mexico', 'The markets of Oaxaca', 'Winter'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Home',
                'notes' => [
                    [
                        'title' => 'Wi-Fi information',
                        'body' => <<<'MARKDOWN'
                            **Network**: `Apartment-20`

                            **Password**: `not-a-real-key-1994`

                            The guest network is `Apartment-20-Guest`, for Joey, who never remembers the other one.
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Home maintenance',
                        'body' => <<<'MARKDOWN'
                            ## This season

                            - [x] Descale the coffee machine
                            - [x] Replace the smoke detector battery
                            - [ ] Clean the oven, properly this time
                            - [ ] Ask the super about the radiator
                            - [ ] Re-grout the bathroom tiles
                            MARKDOWN,
                    ],
                ],
                'lists' => [
                    [
                        'title' => 'Appliances',
                        'description' => 'What is in the apartment, and until when somebody else pays to fix it.',
                        'columns' => ['Appliance', 'Brand', 'Model', 'Bought on', 'Warranty until'],
                        'items' => [
                            ['Refrigerator', 'Sub-Zero', 'BI-36U', '2003-02-10', '2008-02-10'],
                            ['Stand mixer', 'KitchenAid', 'Artisan', '2001-11-20', '2002-11-20'],
                            ['Dishwasher', 'Miele', 'G 7000', '2004-06-01', '2006-06-01'],
                            ['Coffee machine', 'Krups', 'XP 1500', '1999-12-24', null],
                        ],
                    ],
                    [
                        'title' => 'Maintenance schedule',
                        'description' => 'What needs doing, and how often.',
                        'columns' => ['Task', 'Frequency', 'Last done'],
                        'items' => [
                            ['Clean the oven', 'Every month', '2026-10-02'],
                            ['Sharpen the knives', 'Every two weeks', '2026-11-01'],
                            ['Defrost the freezer', 'Twice a year', '2026-04-15'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Health and Fitness',
                'notes' => [
                    [
                        'title' => 'Workout routine',
                        'body' => <<<'MARKDOWN'
                            ## Monday, Wednesday, Friday

                            1. Run along the river, 5 km
                            2. Squats, lunges, push ups: three rounds
                            3. Stretch for ten minutes, no skipping

                            ## Tuesday, Thursday

                            Yoga with Phoebe, if she remembers to come.
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Health goals',
                        'body' => <<<'MARKDOWN'
                            - Run a half marathon before the end of the year
                            - Eat lunch sitting down, at least on days off
                            - Sleep seven hours, even after a double shift
                            MARKDOWN,
                    ],
                ],
                'lists' => [
                    [
                        'title' => 'Workout history',
                        'description' => 'What I did, so I can beat it next time.',
                        'columns' => ['Date', 'Workout', 'Duration', 'Note'],
                        'items' => [
                            ['2026-11-02', 'Run', '32 minutes', 'Personal best'],
                            ['2026-11-04', 'Strength', '45 minutes', null],
                            ['2026-11-05', 'Yoga', '60 minutes', 'Phoebe came'],
                            ['2026-11-07', 'Run', '35 minutes', 'Rain the whole way'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Books and Movies',
                'notes' => [
                    [
                        'title' => 'Favorite books',
                        'body' => <<<'MARKDOWN'
                            - *Kitchen Confidential*, which I disagree with on every page and read every year
                            - *The Joy of Cooking*, the 1975 edition, with my grandmother's notes in the margins
                            - *Little Women*, which Joey still has not finished
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Movie recommendations',
                        'body' => <<<'MARKDOWN'
                            ## From friends

                            - **Chandler**: *Die Hard*. He says it is a Christmas movie.
                            - **Rachel**: *Weekend at Bernie's*. I have questions.
                            - **Ross**: anything with dinosaurs, which narrows it down to one.
                            MARKDOWN,
                    ],
                ],
                'lists' => [
                    [
                        'title' => 'Reading list',
                        'description' => 'Books waiting on the nightstand.',
                        'columns' => ['Title', 'Author', 'Status'],
                        'items' => [
                            ['Salt, Fat, Acid, Heat', 'Samin Nosrat', 'Reading'],
                            ['The Food Lab', 'J. Kenji López-Alt', 'Not started'],
                            ['Mastering the Art of French Cooking', 'Julia Child', 'Finished'],
                        ],
                    ],
                    [
                        'title' => 'Movies watched',
                        'description' => 'What we watched on movie night, and who picked it.',
                        'columns' => ['Title', 'Picked by', 'Rating'],
                        'items' => [
                            ['Die Hard', 'Chandler', '4/5'],
                            ['Yentl', 'Monica', '5/5'],
                            ['Jurassic Park', 'Ross', '3/5'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Food and Recipes',
                'notes' => [
                    [
                        'title' => "Grandma's lasagna",
                        'body' => <<<'MARKDOWN'
                            ## Ingredients

                            - 12 sheets of fresh pasta
                            - 500 g ricotta, 300 g mozzarella, a lot of parmesan
                            - The meat sauce from the freezer, two jars

                            ## Method

                            1. Heat the oven to 190 °C.
                            2. Layer sauce, pasta, ricotta, mozzarella. Repeat four times.
                            3. Finish with sauce and parmesan, cover with foil.
                            4. Bake 45 minutes, uncover, bake 15 more.

                            *Never* make it vegetarian without telling people first.
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Favorite cocktails',
                        'body' => <<<'MARKDOWN'
                            ## Negroni

                            Equal parts gin, Campari and sweet vermouth. Stir, never shake.

                            ## Margarita

                            Tequila, lime, triple sec. Salt on half the rim, for the people who do not like salt.
                            MARKDOWN,
                    ],
                ],
                'lists' => [
                    [
                        'title' => 'Favorite restaurants',
                        'description' => 'Where to eat when somebody else is cooking.',
                        'columns' => ['Restaurant', 'Cuisine', 'Neighbourhood', 'Note'],
                        'items' => [
                            ['Alessandro\'s', 'Italian', 'Greenwich Village', 'I used to cook here'],
                            ['Javu', 'French', 'Upper East Side', null],
                            ['The Moondance Diner', 'Diner', 'SoHo', 'Best pancakes in the city'],
                        ],
                    ],
                    [
                        'title' => 'Recipes',
                        'description' => 'Every recipe I make, and how long it takes.',
                        'columns' => ['Recipe', 'Course', 'Time'],
                        'items' => [
                            ['Grandma\'s lasagna', 'Main', '2 hours'],
                            ['Candy', 'Dessert', '1 hour'],
                            ['Thanksgiving turkey', 'Main', '6 hours'],
                            ['Mockolate cookies', 'Dessert', '30 minutes'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Technology',
                'notes' => [
                    [
                        'title' => 'Home server setup',
                        'body' => <<<'MARKDOWN'
                            Chandler set this up. These notes are for when he is not here.

                            ## Hardware

                            - A small box under the television
                            - Two disks, mirrored

                            ## Restarting it

                            ```
                            sudo systemctl restart recipes
                            ```
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Useful commands',
                        'body' => <<<'MARKDOWN'
                            ## Check the disk

                            ```
                            df -h
                            ```

                            ## Find the large files

                            ```
                            du -sh * | sort -h
                            ```

                            Run `uptime` to see how long it has been running.
                            MARKDOWN,
                    ],
                ],
                'lists' => [
                    [
                        'title' => 'Software subscriptions',
                        'description' => 'What we pay for every month, so nothing renews by surprise.',
                        'columns' => ['Service', 'Price', 'Renews on'],
                        'items' => [
                            ['Music streaming', '10.99', '2026-12-01'],
                            ['Recipe manager', '4.99', '2027-03-15'],
                            ['Cloud backup', '2.99', '2026-11-28'],
                        ],
                    ],
                    [
                        'title' => 'Devices',
                        'description' => 'Every device in the apartment, and who uses it.',
                        'columns' => ['Device', 'Owner', 'Bought in'],
                        'items' => [
                            ['Laptop', 'Monica', '2024'],
                            ['Phone', 'Monica', '2025'],
                            ['Tablet', 'Chandler', '2023'],
                            ['Home server', 'Chandler', null],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Ideas and Projects',
                'notes' => [
                    [
                        'title' => 'App ideas',
                        'body' => <<<'MARKDOWN'
                            - An app that tells you when the milk in the fridge expires
                            - A seating plan builder for dinner parties that knows who should not sit next to whom
                            - A timer for Thanksgiving that runs every dish backwards from the moment you serve
                            MARKDOWN,
                    ],
                    [
                        'title' => 'Things to learn',
                        'body' => <<<'MARKDOWN'
                            - [x] Make fresh pasta without a machine
                            - [ ] Butcher a whole fish
                            - [ ] Speak enough Italian to argue with a supplier
                            - [ ] Play the guitar, better than Phoebe
                            MARKDOWN,
                    ],
                ],
                'lists' => [
                    [
                        'title' => 'Side projects',
                        'description' => 'Things I work on when the restaurant is closed.',
                        'columns' => ['Project', 'Status', 'Next step'],
                        'items' => [
                            ['Family cookbook', 'In progress', 'Write the dessert chapter'],
                            ['Catering business', 'Paused', 'Find a new partner'],
                            ['Cooking classes', 'Idea', null],
                        ],
                    ],
                ],
            ],
        ];
    }
}
