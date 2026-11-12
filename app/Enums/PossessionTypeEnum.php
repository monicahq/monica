<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionTypeEnum: string implements Translatable
{
    case Comics = 'comics';
    case TradingCards = 'trading_cards';
    case VinylRecords = 'vinyl_records';
    case Cd = 'cd';
    case Dvd = 'dvd';
    case Coins = 'coins';
    case Stamps = 'stamps';
    case Books = 'books';
    case ActionFigures = 'action_figures';
    case VideoGames = 'video_games';
    case Watches = 'watches';
    case Wine = 'wine';
    case Cars = 'cars';
    case Bicycles = 'bicycles';
    case HouseholdAppliances = 'household_appliances';
    case Electronics = 'electronics';
    case Furniture = 'furniture';
    case Tools = 'tools';
    case Clothing = 'clothing';
    case Jewellery = 'jewellery';
    case MusicalInstruments = 'musical_instruments';
    case Art = 'art';
    case Sports = 'sports';
    case Homes = 'homes';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Comics => 'Comics',
            self::TradingCards => 'Trading Cards',
            self::VinylRecords => 'Vinyl Records',
            self::Cd => 'CD',
            self::Dvd => 'DVD',
            self::Coins => 'Coins',
            self::Stamps => 'Stamps',
            self::Books => 'Books',
            self::ActionFigures => 'Action Figures / Toys',
            self::VideoGames => 'Video Games',
            self::Watches => 'Watches',
            self::Wine => 'Wine',
            self::Cars => 'Cars',
            self::Bicycles => 'Bicycles',
            self::HouseholdAppliances => 'Household appliances',
            self::Electronics => 'Electronics and computers',
            self::Furniture => 'Furniture',
            self::Tools => 'Tools and equipment',
            self::Clothing => 'Clothing and accessories',
            self::Jewellery => 'Jewellery',
            self::MusicalInstruments => 'Musical instruments',
            self::Art => 'Art and decoration',
            self::Sports => 'Sports and outdoors',
            self::Homes => 'Homes',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Comics, self::Books => 'book',
            self::TradingCards, self::Furniture => 'layers',
            self::VinylRecords, self::Cd, self::MusicalInstruments => 'music',
            self::Dvd => 'file',
            self::Coins => 'coin',
            self::Stamps => 'mail',
            self::ActionFigures => 'people',
            self::VideoGames => 'target',
            self::Watches => 'star',
            self::Wine => 'food',
            self::Cars => 'car',
            self::Bicycles => 'activity',
            self::HouseholdAppliances, self::Homes => 'home',
            self::Electronics => 'phone',
            self::Tools => 'briefcase',
            self::Clothing => 'profile',
            self::Jewellery => 'gift',
            self::Art => 'heart',
            self::Sports => 'ball',
            self::Other => 'tag',
        };
    }

    public function colour(): VaultColourEnum
    {
        return match ($this) {
            self::Comics, self::Books, self::Electronics => VaultColourEnum::Blue,
            self::VinylRecords, self::Cd, self::Dvd, self::VideoGames, self::MusicalInstruments => VaultColourEnum::Violet,
            self::TradingCards, self::Coins, self::Stamps, self::Watches, self::Jewellery => VaultColourEnum::Amber,
            self::Wine, self::ActionFigures, self::Art => VaultColourEnum::Plum,
            self::Cars, self::Bicycles, self::Tools => VaultColourEnum::Rust,
            self::HouseholdAppliances, self::Furniture, self::Homes => VaultColourEnum::Teal,
            self::Clothing, self::Sports => VaultColourEnum::Green,
            self::Other => VaultColourEnum::Slate,
        };
    }

    /**
     * The fields of the type, block by block. A block without a name holds the standalone fields and
     * always comes first.
     *
     * @return list<array{name: ?string, fields: list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>}>}>
     */
    public function blocks(): array
    {
        return match ($this) {
            self::Comics => [
                self::standalone([self::rating()]),
                self::group('Publishing info', [
                    self::number('Issue #'),
                    self::select('Publisher', ['Marvel', 'DC', 'Image', 'Dark Horse', 'Independent']),
                    self::text('Writer'),
                    self::text('Artist'),
                    self::date('Cover date'),
                ]),
                self::group('Condition & grading', [
                    self::boolean('Variant'),
                    self::boolean('Signed'),
                ]),
            ],
            self::TradingCards => [
                self::group('Card details', [
                    self::text('Card number'),
                    self::text('Set/series'),
                    self::text('Player/character'),
                ]),
                self::group('Condition & grading', [
                    self::select('Grade', ['PSA 10', 'PSA 9', 'Raw', 'Other']),
                    self::boolean('Rookie card'),
                    self::boolean('Autographed'),
                ]),
            ],
            self::VinylRecords => [
                self::standalone([self::rating()]),
                self::group('Release info', [
                    self::text('Artist'),
                    self::text('Album'),
                    self::number('Release year'),
                ]),
                self::group('Pressing details', [
                    self::text('Pressing/edition'),
                    self::select('Speed', ['33', '45', '78']),
                    self::text('Coloured vinyl'),
                ]),
            ],
            self::Cd => [
                self::standalone([self::rating()]),
                self::group('Release info', [
                    self::text('Artist'),
                    self::text('Album'),
                    self::text('Record label'),
                    self::number('Release year'),
                    self::select('Genre', ['Rock', 'Pop', 'Jazz', 'Classical', 'Hip hop', 'Electronic', 'Folk', 'Other']),
                ]),
                self::group('Extras', [
                    self::boolean('Bonus disc'),
                    self::boolean('Signed'),
                ]),
            ],
            self::Dvd => [
                self::standalone([self::rating()]),
                self::group('Release info', [
                    self::text('Title'),
                    self::text('Director'),
                    self::text('Studio'),
                    self::number('Release year'),
                ]),
                self::group('Edition', [
                    self::select('Region code', ['1', '2', '3', '4', '5', '6', 'Region free']),
                    self::boolean('Special edition'),
                    self::boolean('Box set'),
                ]),
            ],
            self::Coins => [
                self::group('Identification', [
                    self::text('Denomination'),
                    self::number('Year minted'),
                    self::text('Mint mark'),
                    self::text('Country'),
                ]),
                self::group('Condition & metal', [
                    self::select('Grade', ['MS-70', 'MS-65', 'MS-60', 'AU-58', 'AU-50', 'XF-45', 'XF-40', 'VF-30', 'VF-20', 'F-15', 'F-12', 'VG-10', 'VG-8', 'G-6', 'G-4', 'AG-3', 'FR-2', 'PR-1']),
                    self::select('Metal', ['Silver', 'Gold', 'Copper', 'Other']),
                ]),
            ],
            self::Stamps => [
                self::group('Issue info', [
                    self::text('Country'),
                    self::number('Year issued'),
                    self::text('Denomination'),
                ]),
                self::group('Condition', [
                    self::boolean('Perforated'),
                    self::select('Cancelled/mint', ['Cancelled (used)', 'Mint (unused)']),
                ]),
            ],
            self::Books => [
                self::standalone([self::rating()]),
                self::group('Publishing info', [
                    self::text('Author'),
                    self::text('Edition'),
                    self::text('Publisher'),
                    self::text('ISBN'),
                ]),
                self::group('Copy details', [
                    self::boolean('First edition'),
                    self::boolean('Signed'),
                ]),
            ],
            self::ActionFigures => [
                self::group('Product info', [
                    self::text('Brand'),
                    self::text('Line/series'),
                    self::number('Year'),
                    self::text('Scale'),
                ]),
                self::group('Condition', [
                    self::boolean('In box / MIB'),
                ]),
            ],
            self::VideoGames => [
                self::standalone([self::rating()]),
                self::group('Release info', [
                    self::select('Platform', ['PC', 'PS5', 'PS4', 'PS3', 'PS2', 'PS1', 'Xbox Series', 'Xbox One', 'Xbox 360', 'Xbox', 'Switch', 'Wii U', 'Wii', 'GameCube', 'N64', 'SNES', 'NES', 'Game Boy', 'DS', '3DS', 'Mega Drive', 'Dreamcast', 'Other']),
                    self::text('Publisher'),
                    self::number('Release year'),
                    self::select('Region', ['NTSC', 'PAL']),
                ]),
                self::group('Condition', [
                    self::boolean('Complete in box'),
                ]),
            ],
            self::Watches => [
                self::group('Model info', [
                    self::text('Brand'),
                    self::text('Model'),
                    self::select('Movement', ['Automatic', 'Quartz', 'Manual']),
                    self::text('Serial number'),
                ]),
                self::group('Condition', [
                    self::boolean('Box & papers'),
                ]),
            ],
            self::Wine => [
                self::standalone([
                    self::select('Bottle size', ['Split/187ml', 'Half/375ml', 'Standard/750ml', 'Magnum/1.5L', 'Double Magnum/3L', 'Jeroboam/4.5L', 'Imperial/6L']),
                    self::rating(),
                ]),
                self::group('Origin', [
                    self::text('Producer/winery'),
                    self::number('Vintage'),
                    self::text('Region'),
                    self::text('Varietal'),
                ]),
            ],
            self::Cars => [
                self::group('Identification', [
                    self::text('Make'),
                    self::text('Model'),
                    self::text('Trim'),
                    self::number('Model year'),
                    self::text('Registration number'),
                    self::date('First registration date'),
                ]),
                self::group('Characteristics', [
                    self::text('Engine'),
                    self::select('Fuel', ['Petrol', 'Diesel', 'Electric', 'Hybrid', 'Plug-in hybrid', 'LPG', 'Other']),
                    self::select('Transmission', ['Manual', 'Automatic', 'Other']),
                    self::text('Colour'),
                    self::number('Power in kW'),
                ]),
                self::group('Maintenance references', [
                    self::text('Tyre size'),
                    self::text('Oil reference'),
                ]),
            ],
            self::Bicycles => [
                self::group('Identification', [
                    self::text('Brand'),
                    self::text('Model'),
                    self::number('Year'),
                    self::select('Use', ['City', 'Road', 'Gravel', 'Mountain', 'Cargo', 'BMX', 'Other']),
                ]),
                self::group('Equipment', [
                    self::text('Frame size'),
                    self::text('Wheel size'),
                    self::text('Drivetrain'),
                    self::text('Brakes'),
                    self::boolean('Electric assist'),
                    self::number('Battery capacity in Wh'),
                ]),
            ],
            self::HouseholdAppliances => [
                self::group('Identification', [
                    self::select('Category', ['Dishwasher', 'Washing machine', 'Tumble dryer', 'Refrigerator', 'Freezer', 'Oven', 'Hob', 'Vacuum cleaner', 'Other']),
                    self::text('Brand'),
                    self::text('Model'),
                    self::text('Product reference'),
                ]),
                self::group('Characteristics', [
                    self::text('Dimensions'),
                    self::text('Capacity'),
                    self::number('Power in W'),
                    self::text('Stated energy class'),
                ]),
                self::group('Installation', [
                    self::date('Installation date'),
                    self::text('Filter or consumable reference'),
                ]),
            ],
            self::Electronics => [
                self::group('Identification', [
                    self::select('Category', ['Computer', 'Phone', 'Tablet', 'Monitor', 'Television', 'Audio', 'Network', 'Photography', 'Other']),
                    self::text('Brand'),
                    self::text('Model'),
                    self::number('Year'),
                ]),
                self::group('Configuration', [
                    self::text('Processor'),
                    self::number('Memory in GB'),
                    self::number('Storage in GB'),
                    self::text('Operating system'),
                    self::text('Ports'),
                ]),
                self::group('Accessories and consumables', [
                    self::text('Charger reference'),
                    self::text('Battery reference'),
                ]),
            ],
            self::Furniture => [
                self::group('Identification', [
                    self::select('Category', ['Table', 'Chair', 'Sofa', 'Bed', 'Wardrobe', 'Shelf', 'Desk', 'Other']),
                    self::text('Brand'),
                    self::text('Model or range'),
                    self::text('Designer'),
                ]),
                self::group('Dimensions and materials', [
                    self::number('Width in cm'),
                    self::number('Height in cm'),
                    self::number('Depth in cm'),
                    self::text('Materials'),
                    self::text('Colour or finish'),
                ]),
                self::group('Upkeep', [
                    self::text('Fabric or covering reference'),
                    self::text('Care instructions'),
                ]),
            ],
            self::Tools => [
                self::group('Identification', [
                    self::text('Category'),
                    self::text('Brand'),
                    self::text('Model'),
                    self::text('Product reference'),
                ]),
                self::group('Characteristics', [
                    self::select('Power source', ['Manual', 'Mains', 'Battery', 'Combustion engine', 'Pneumatic', 'Other']),
                    self::number('Voltage in V'),
                    self::number('Power in W'),
                    self::text('Battery platform'),
                ]),
                self::group('Compatibility', [
                    self::text('Compatible accessories'),
                    self::text('Consumable references'),
                ]),
            ],
            self::Clothing => [
                self::group('Identification', [
                    self::text('Category'),
                    self::text('Brand'),
                    self::text('Model or range'),
                ]),
                self::group('Characteristics', [
                    self::text('Size'),
                    self::text('Colour'),
                    self::text('Composition'),
                ]),
                self::group('Upkeep', [
                    self::text('Washing instructions'),
                    self::text('Drying instructions'),
                ]),
            ],
            self::Jewellery => [
                self::group('Identification', [
                    self::select('Category', ['Ring', 'Necklace', 'Bracelet', 'Earrings', 'Pendant', 'Other']),
                    self::text('Brand'),
                    self::text('Model'),
                ]),
                self::group('Composition', [
                    self::text('Metal'),
                    self::text('Fineness'),
                    self::text('Stones'),
                    self::number('Weight in g'),
                    self::text('Size or dimensions'),
                ]),
                self::group('Authentication', [
                    self::text('Hallmark'),
                    self::text('Certificate reference'),
                    self::text('Certifying body'),
                ]),
            ],
            self::MusicalInstruments => [
                self::group('Identification', [
                    self::text('Instrument'),
                    self::text('Brand'),
                    self::text('Model'),
                    self::number('Year'),
                ]),
                self::group('Characteristics', [
                    self::text('Materials'),
                    self::text('Dimensions or format'),
                    self::text('Finish'),
                ]),
                self::group('Configuration', [
                    self::text('Tuning'),
                    self::text('Strings or consumables reference'),
                    self::text('Particular features'),
                ]),
            ],
            self::Art => [
                self::group('Creation', [
                    self::select('Category', ['Painting', 'Drawing', 'Photograph', 'Sculpture', 'Print', 'Decorative object', 'Other']),
                    self::text('Artist or maker'),
                    self::number('Year of creation'),
                    self::text('Technique'),
                    self::text('Materials'),
                ]),
                self::group('Dimensions and edition', [
                    self::text('Dimensions'),
                    self::text('Copy number'),
                    self::number('Edition size'),
                    self::boolean('Signed'),
                ]),
                self::group('Presentation', [
                    self::text('Frame or mount'),
                    self::text('Conservation instructions'),
                ]),
            ],
            self::Sports => [
                self::group('Identification', [
                    self::text('Activity'),
                    self::text('Equipment category'),
                    self::text('Brand'),
                    self::text('Model'),
                ]),
                self::group('Characteristics', [
                    self::text('Size or dimensions'),
                    self::number('Weight in kg'),
                    self::text('Materials'),
                ]),
                self::group('Use', [
                    self::text('Season or conditions of use'),
                    self::text('Compatibility'),
                    self::text('Care instructions'),
                ]),
            ],
            self::Homes => [
                self::group('Building', [
                    self::select('Category', ['House', 'Apartment', 'Chalet', 'Other']),
                    self::number('Year built'),
                    self::number('Living area in m²'),
                    self::number('Land area in m²'),
                    self::number('Number of rooms'),
                ]),
                self::group('Administrative references', [
                    self::text('Cadastral reference'),
                    self::text('Lot number'),
                ]),
                self::group('Home equipment', [
                    self::text('Heating type'),
                    self::text('Heating energy'),
                    self::text('Hot water'),
                    self::text('Ventilation'),
                ]),
            ],
            self::Other => [],
        };
    }

    /**
     * The options written the same way in every language: grades, speeds, region codes, platforms
     * and publishers. They never reach the translation files and are written as they are, even
     * where a word like "Image" is translated elsewhere in the product.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return [
            'PSA 10', 'PSA 9',
            '33', '45', '78',
            '1', '2', '3', '4', '5', '6',
            'MS-70', 'MS-65', 'MS-60', 'AU-58', 'AU-50', 'XF-45', 'XF-40', 'VF-30', 'VF-20',
            'F-15', 'F-12', 'VG-10', 'VG-8', 'G-6', 'G-4', 'AG-3', 'FR-2', 'PR-1',
            'PC', 'PS5', 'PS4', 'PS3', 'PS2', 'PS1', 'Xbox Series', 'Xbox One', 'Xbox 360', 'Xbox', 'Switch', 'Wii U', 'Wii',
            'GameCube', 'N64', 'SNES', 'NES', 'Game Boy', 'DS', '3DS', 'Mega Drive', 'Dreamcast',
            'NTSC', 'PAL',
            'BMX',
            'Marvel', 'DC', 'Image', 'Dark Horse',
        ];
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        $keys = [$this->label()];

        foreach ($this->blocks() as $block) {
            if ($block['name'] !== null) {
                $keys[] = $block['name'];
            }

            foreach ($block['fields'] as $field) {
                $keys[] = $field['name'];

                foreach ($field['options'] as $option) {
                    $keys[] = $option;
                }
            }
        }

        return array_values(array_unique(array_diff($keys, self::codes())));
    }

    /**
     * @param  list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>}>  $fields
     * @return array{name: ?string, fields: list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>}>}
     */
    private static function standalone(array $fields): array
    {
        return ['name' => null, 'fields' => $fields];
    }

    /**
     * @param  list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>}>  $fields
     * @return array{name: ?string, fields: list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>}>}
     */
    private static function group(string $name, array $fields): array
    {
        return ['name' => $name, 'fields' => $fields];
    }

    /** @return array{name: string, kind: PossessionFieldKindEnum, options: list<string>} */
    private static function text(string $name): array
    {
        return self::field($name, PossessionFieldKindEnum::Text);
    }

    /** @return array{name: string, kind: PossessionFieldKindEnum, options: list<string>} */
    private static function number(string $name): array
    {
        return self::field($name, PossessionFieldKindEnum::Number);
    }

    /** @return array{name: string, kind: PossessionFieldKindEnum, options: list<string>} */
    private static function date(string $name): array
    {
        return self::field($name, PossessionFieldKindEnum::Date);
    }

    /** @return array{name: string, kind: PossessionFieldKindEnum, options: list<string>} */
    private static function boolean(string $name): array
    {
        return self::field($name, PossessionFieldKindEnum::Boolean);
    }

    /** @return array{name: string, kind: PossessionFieldKindEnum, options: list<string>} */
    private static function rating(): array
    {
        return self::field('My rating', PossessionFieldKindEnum::Rating);
    }

    /**
     * @param  list<string>  $options
     * @return array{name: string, kind: PossessionFieldKindEnum, options: list<string>}
     */
    private static function select(string $name, array $options): array
    {
        return self::field($name, PossessionFieldKindEnum::Select, $options);
    }

    /**
     * @param  list<string>  $options
     * @return array{name: string, kind: PossessionFieldKindEnum, options: list<string>}
     */
    private static function field(string $name, PossessionFieldKindEnum $kind, array $options = []): array
    {
        return ['name' => $name, 'kind' => $kind, 'options' => $options];
    }
}
