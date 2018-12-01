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


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeederTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('countries')->truncate();

        DB::table('countries')->insert(['iso' => 'us', 'country' => 'United States']);
        DB::table('countries')->insert(['iso' => 'ca', 'country' => 'Canada']);
        DB::table('countries')->insert(['iso' => 'fr', 'country' => 'France']);
        DB::table('countries')->insert(['iso' => 'de', 'country' => 'Germany']);
        DB::table('countries')->insert(['iso' => 'ao', 'country' => 'Angola']);
        DB::table('countries')->insert(['iso' => 'bf', 'country' => 'Burkina Faso']);
        DB::table('countries')->insert(['iso' => 'bi', 'country' => 'Burundi']);
        DB::table('countries')->insert(['iso' => 'bj', 'country' => 'Benin']);
        DB::table('countries')->insert(['iso' => 'bw', 'country' => 'Botswana']);
        DB::table('countries')->insert(['iso' => 'cd', 'country' => 'Democratic Republic Congo']);
        DB::table('countries')->insert(['iso' => 'cf', 'country' => 'Central African Republic']);
        DB::table('countries')->insert(['iso' => 'cg', 'country' => 'Congo']);
        DB::table('countries')->insert(['iso' => 'ci', 'country' => "Côte d'Ivoire"]);
        DB::table('countries')->insert(['iso' => 'cm', 'country' => 'Cameroon']);
        DB::table('countries')->insert(['iso' => 'cv', 'country' => 'Cape Verde']);
        DB::table('countries')->insert(['iso' => 'dj', 'country' => 'Djibouti']);
        DB::table('countries')->insert(['iso' => 'dz', 'country' => 'Algeria']);
        DB::table('countries')->insert(['iso' => 'eg', 'country' => 'Egypt']);
        DB::table('countries')->insert(['iso' => 'eh', 'country' => 'Western Sahara']);
        DB::table('countries')->insert(['iso' => 'er', 'country' => 'Eritrea']);
        DB::table('countries')->insert(['iso' => 'et', 'country' => 'Ethiopia']);
        DB::table('countries')->insert(['iso' => 'ga', 'country' => 'Gabon']);
        DB::table('countries')->insert(['iso' => 'gh', 'country' => 'Ghana']);
        DB::table('countries')->insert(['iso' => 'gm', 'country' => 'Gambia']);
        DB::table('countries')->insert(['iso' => 'gn', 'country' => 'Guinea']);
        DB::table('countries')->insert(['iso' => 'gq', 'country' => 'Equatorial Guinea']);
        DB::table('countries')->insert(['iso' => 'gw', 'country' => 'Guinea-Bissau']);
        DB::table('countries')->insert(['iso' => 'ke', 'country' => 'Kenya']);
        DB::table('countries')->insert(['iso' => 'km', 'country' => 'Comoros']);
        DB::table('countries')->insert(['iso' => 'lr', 'country' => 'Liberia']);
        DB::table('countries')->insert(['iso' => 'ls', 'country' => 'Lesotho']);
        DB::table('countries')->insert(['iso' => 'ly', 'country' => 'Libya']);
        DB::table('countries')->insert(['iso' => 'ma', 'country' => 'Morocco']);
        DB::table('countries')->insert(['iso' => 'mg', 'country' => 'Madagascar']);
        DB::table('countries')->insert(['iso' => 'ml', 'country' => 'Mali']);
        DB::table('countries')->insert(['iso' => 'mr', 'country' => 'Mauritania']);
        DB::table('countries')->insert(['iso' => 'mu', 'country' => 'Mauritius']);
        DB::table('countries')->insert(['iso' => 'mw', 'country' => 'Malawi']);
        DB::table('countries')->insert(['iso' => 'mz', 'country' => 'Mozambique']);
        DB::table('countries')->insert(['iso' => 'na', 'country' => 'Namibia']);
        DB::table('countries')->insert(['iso' => 'ne', 'country' => 'Niger']);
        DB::table('countries')->insert(['iso' => 'ng', 'country' => 'Nigeria']);
        DB::table('countries')->insert(['iso' => 're', 'country' => 'Réunion']);
        DB::table('countries')->insert(['iso' => 'rw', 'country' => 'Rwanda']);
        DB::table('countries')->insert(['iso' => 'sc', 'country' => 'Seychelles']);
        DB::table('countries')->insert(['iso' => 'sd', 'country' => 'Sudan']);
        DB::table('countries')->insert(['iso' => 'sh', 'country' => 'Saint Helena, Ascension and Tristan da Cunha']);
        DB::table('countries')->insert(['iso' => 'sl', 'country' => 'Sierra Leone']);
        DB::table('countries')->insert(['iso' => 'sn', 'country' => 'Senegal']);
        DB::table('countries')->insert(['iso' => 'so', 'country' => 'Somalia']);
        DB::table('countries')->insert(['iso' => 'ss', 'country' => 'South Sudan']);
        DB::table('countries')->insert(['iso' => 'st', 'country' => 'Sao Tome and Principe']);
        DB::table('countries')->insert(['iso' => 'sz', 'country' => 'Swaziland']);
        DB::table('countries')->insert(['iso' => 'td', 'country' => 'Chad']);
        DB::table('countries')->insert(['iso' => 'tg', 'country' => 'Togo']);
        DB::table('countries')->insert(['iso' => 'tn', 'country' => 'Tunisia']);
        DB::table('countries')->insert(['iso' => 'tz', 'country' => 'Tanzania']);
        DB::table('countries')->insert(['iso' => 'ug', 'country' => 'Uganda']);
        DB::table('countries')->insert(['iso' => 'yt', 'country' => 'Mayotte']);
        DB::table('countries')->insert(['iso' => 'za', 'country' => 'South Africa']);
        DB::table('countries')->insert(['iso' => 'zm', 'country' => 'Zambia']);
        DB::table('countries')->insert(['iso' => 'zw', 'country' => 'Zimbabwe']);
        DB::table('countries')->insert(['iso' => 'af', 'country' => 'Afghanistan']);
        DB::table('countries')->insert(['iso' => 'am', 'country' => 'Armenia']);
        DB::table('countries')->insert(['iso' => 'aq', 'country' => 'Antartica']);
        DB::table('countries')->insert(['iso' => 'az', 'country' => 'Azerbaijan']);
        DB::table('countries')->insert(['iso' => 'bd', 'country' => 'Bangladesh']);
        DB::table('countries')->insert(['iso' => 'bh', 'country' => 'Bahrain']);
        DB::table('countries')->insert(['iso' => 'bn', 'country' => 'Brunei Darussalam']);
        DB::table('countries')->insert(['iso' => 'bt', 'country' => 'Bhutan']);
        DB::table('countries')->insert(['iso' => 'bv', 'country' => 'Bouvet Island']);
        DB::table('countries')->insert(['iso' => 'cc', 'country' => 'Cocos (Keeling) Islands']);
        DB::table('countries')->insert(['iso' => 'cn', 'country' => 'China']);
        DB::table('countries')->insert(['iso' => 'ct', 'country' => 'Cyprus']);
        DB::table('countries')->insert(['iso' => 'cx', 'country' => 'Christmas Island']);
        DB::table('countries')->insert(['iso' => 'ge', 'country' => 'Georgia']);
        DB::table('countries')->insert(['iso' => 'gs', 'country' => 'South Georgia and the South Sandwich Islands']);
        DB::table('countries')->insert(['iso' => 'hk', 'country' => 'Hong Kong']);
        DB::table('countries')->insert(['iso' => 'hm', 'country' => 'Heard Island and McDonald Islands']);
        DB::table('countries')->insert(['iso' => 'id', 'country' => 'Indonesia']);
        DB::table('countries')->insert(['iso' => 'il', 'country' => 'Israel']);
        DB::table('countries')->insert(['iso' => 'io', 'country' => 'British Indian Ocean Territory']);
        DB::table('countries')->insert(['iso' => 'in', 'country' => 'India']);
        DB::table('countries')->insert(['iso' => 'iq', 'country' => 'Iraq']);
        DB::table('countries')->insert(['iso' => 'ir', 'country' => 'Iran']);
        DB::table('countries')->insert(['iso' => 'jo', 'country' => 'Jordan']);
        DB::table('countries')->insert(['iso' => 'jp', 'country' => 'Japan']);
        DB::table('countries')->insert(['iso' => 'kg', 'country' => 'Kyrgyzstan']);
        DB::table('countries')->insert(['iso' => 'kh', 'country' => 'Cambodia']);
        DB::table('countries')->insert(['iso' => 'kp', 'country' => 'North Korea']);
        DB::table('countries')->insert(['iso' => 'kr', 'country' => 'South Korea']);
        DB::table('countries')->insert(['iso' => 'kw', 'country' => 'Kuwait']);
        DB::table('countries')->insert(['iso' => 'kz', 'country' => 'Kazakhstan']);
        DB::table('countries')->insert(['iso' => 'la', 'country' => 'Lao People Democratic Republic']);
        DB::table('countries')->insert(['iso' => 'lb', 'country' => 'Lebanon']);
        DB::table('countries')->insert(['iso' => 'lk', 'country' => 'Sri Lanka']);
        DB::table('countries')->insert(['iso' => 'mm', 'country' => 'Myanmar']);
        DB::table('countries')->insert(['iso' => 'mn', 'country' => 'Mongolia']);
        DB::table('countries')->insert(['iso' => 'mo', 'country' => 'Macao']);
        DB::table('countries')->insert(['iso' => 'mv', 'country' => 'Maldives']);
        DB::table('countries')->insert(['iso' => 'my', 'country' => 'Malaysia']);
        DB::table('countries')->insert(['iso' => 'nc', 'country' => 'New Caledonia']);
        DB::table('countries')->insert(['iso' => 'np', 'country' => 'Nepal']);
        DB::table('countries')->insert(['iso' => 'om', 'country' => 'Oman']);
        DB::table('countries')->insert(['iso' => 'ph', 'country' => 'Philippines']);
        DB::table('countries')->insert(['iso' => 'pk', 'country' => 'Pakistan']);
        DB::table('countries')->insert(['iso' => 'qa', 'country' => 'Qatar']);
        DB::table('countries')->insert(['iso' => 'ru', 'country' => 'Russian federation']);
        DB::table('countries')->insert(['iso' => 'sa', 'country' => 'Saudi Arabia']);
        DB::table('countries')->insert(['iso' => 'sg', 'country' => 'Singapore']);
        DB::table('countries')->insert(['iso' => 'sr', 'country' => 'Suriname']);
        DB::table('countries')->insert(['iso' => 'sy', 'country' => 'Syrian Arab Republic']);
        DB::table('countries')->insert(['iso' => 'tf', 'country' => 'French Southern Territories']);
        DB::table('countries')->insert(['iso' => 'th', 'country' => 'Thailand']);
        DB::table('countries')->insert(['iso' => 'tj', 'country' => 'Tajikistan']);
        DB::table('countries')->insert(['iso' => 'tl', 'country' => 'Timor-Leste']);
        DB::table('countries')->insert(['iso' => 'tm', 'country' => 'Turkmenistan']);
        DB::table('countries')->insert(['iso' => 'tr', 'country' => 'Turkey']);
        DB::table('countries')->insert(['iso' => 'tw', 'country' => 'Taiwan']);
        DB::table('countries')->insert(['iso' => 'uz', 'country' => 'Uzbekistan']);
        DB::table('countries')->insert(['iso' => 'vn', 'country' => 'Viet Nam']);
        DB::table('countries')->insert(['iso' => 'ye', 'country' => 'Yemen']);
        DB::table('countries')->insert(['iso' => 'ad', 'country' => 'Andorra']);
        DB::table('countries')->insert(['iso' => 'al', 'country' => 'Albania']);
        DB::table('countries')->insert(['iso' => 'at', 'country' => 'Austria']);
        DB::table('countries')->insert(['iso' => 'ax', 'country' => 'Aland Islands']);
        DB::table('countries')->insert(['iso' => 'ba', 'country' => 'Bosnia and Herzegovina']);
        DB::table('countries')->insert(['iso' => 'be', 'country' => 'Belgium']);
        DB::table('countries')->insert(['iso' => 'bg', 'country' => 'Bulgaria']);
        DB::table('countries')->insert(['iso' => 'by', 'country' => 'Belarus']);
        DB::table('countries')->insert(['iso' => 'ch', 'country' => 'Switzerland']);
        DB::table('countries')->insert(['iso' => 'cz', 'country' => 'Czech Republic']);
        DB::table('countries')->insert(['iso' => 'dk', 'country' => 'Denmark']);
        DB::table('countries')->insert(['iso' => 'ee', 'country' => 'Estonia']);
        DB::table('countries')->insert(['iso' => 'es', 'country' => 'Spain']);
        DB::table('countries')->insert(['iso' => 'fi', 'country' => 'Finland']);
        DB::table('countries')->insert(['iso' => 'fo', 'country' => 'Faroe Islands']);
        DB::table('countries')->insert(['iso' => 'gb', 'country' => 'United Kingdom']);
        DB::table('countries')->insert(['iso' => 'gg', 'country' => 'Guernsey']);
        DB::table('countries')->insert(['iso' => 'gi', 'country' => 'Gibraltar']);
        DB::table('countries')->insert(['iso' => 'gr', 'country' => 'Greece']);
        DB::table('countries')->insert(['iso' => 'hr', 'country' => 'Croatia']);
        DB::table('countries')->insert(['iso' => 'hu', 'country' => 'Hungary']);
        DB::table('countries')->insert(['iso' => 'ie', 'country' => 'Ireland']);
        DB::table('countries')->insert(['iso' => 'im', 'country' => 'Isle of Man']);
        DB::table('countries')->insert(['iso' => 'is', 'country' => 'Iceland']);
        DB::table('countries')->insert(['iso' => 'it', 'country' => 'Italy']);
        DB::table('countries')->insert(['iso' => 'li', 'country' => 'Liechtenstein']);
        DB::table('countries')->insert(['iso' => 'lt', 'country' => 'Lithuania']);
        DB::table('countries')->insert(['iso' => 'lu', 'country' => 'Luxembourg']);
        DB::table('countries')->insert(['iso' => 'lv', 'country' => 'Latvia']);
        DB::table('countries')->insert(['iso' => 'mc', 'country' => 'Monaco']);
        DB::table('countries')->insert(['iso' => 'md', 'country' => 'Moldova']);
        DB::table('countries')->insert(['iso' => 'me', 'country' => 'Montenegro']);
        DB::table('countries')->insert(['iso' => 'mk', 'country' => 'Macedonia']);
        DB::table('countries')->insert(['iso' => 'mt', 'country' => 'Malta']);
        DB::table('countries')->insert(['iso' => 'nl', 'country' => 'Netherlands']);
        DB::table('countries')->insert(['iso' => 'no', 'country' => 'Norway']);
        DB::table('countries')->insert(['iso' => 'pl', 'country' => 'Poland']);
        DB::table('countries')->insert(['iso' => 'pt', 'country' => 'Portugal']);
        DB::table('countries')->insert(['iso' => 'ro', 'country' => 'Romania']);
        DB::table('countries')->insert(['iso' => 'rs', 'country' => 'Serbia']);
        DB::table('countries')->insert(['iso' => 'se', 'country' => 'Sweden']);
        DB::table('countries')->insert(['iso' => 'si', 'country' => 'Slovenia']);
        DB::table('countries')->insert(['iso' => 'sj', 'country' => 'Svalbard and Jan Mayen']);
        DB::table('countries')->insert(['iso' => 'sk', 'country' => 'Slovakia']);
        DB::table('countries')->insert(['iso' => 'sm', 'country' => 'San Marino']);
        DB::table('countries')->insert(['iso' => 'ua', 'country' => 'Ukraine']);
        DB::table('countries')->insert(['iso' => 'va', 'country' => 'Vatican City State']);
        DB::table('countries')->insert(['iso' => 'ag', 'country' => 'Antigua and Barbuda']);
        DB::table('countries')->insert(['iso' => 'ai', 'country' => 'Anguilla']);
        DB::table('countries')->insert(['iso' => 'aw', 'country' => 'Aruba']);
        DB::table('countries')->insert(['iso' => 'bb', 'country' => 'Barbados']);
        DB::table('countries')->insert(['iso' => 'bl', 'country' => 'Saint Barthélemy']);
        DB::table('countries')->insert(['iso' => 'bm', 'country' => 'Bermuda']);
        DB::table('countries')->insert(['iso' => 'bq', 'country' => 'Bonaire  Sint Eustatius and Saba']);
        DB::table('countries')->insert(['iso' => 'bs', 'country' => 'Bahamas']);
        DB::table('countries')->insert(['iso' => 'bz', 'country' => 'Belize']);
        DB::table('countries')->insert(['iso' => 'cr', 'country' => 'Costa Rica']);
        DB::table('countries')->insert(['iso' => 'cu', 'country' => 'Cuba']);
        DB::table('countries')->insert(['iso' => 'cw', 'country' => 'Curaçao']);
        DB::table('countries')->insert(['iso' => 'dm', 'country' => 'Dominica']);
        DB::table('countries')->insert(['iso' => 'do', 'country' => 'Dominican Republic']);
        DB::table('countries')->insert(['iso' => 'gd', 'country' => 'Grenada']);
        DB::table('countries')->insert(['iso' => 'gl', 'country' => 'Greenland']);
        DB::table('countries')->insert(['iso' => 'gp', 'country' => 'Guadeloupe']);
        DB::table('countries')->insert(['iso' => 'gt', 'country' => 'Guatemala']);
        DB::table('countries')->insert(['iso' => 'hn', 'country' => 'Honduras']);
        DB::table('countries')->insert(['iso' => 'ht', 'country' => 'Haiti']);
        DB::table('countries')->insert(['iso' => 'jm', 'country' => 'Jamaica']);
        DB::table('countries')->insert(['iso' => 'kn', 'country' => 'Saint Kitts and Nevis']);
        DB::table('countries')->insert(['iso' => 'ky', 'country' => 'Cayman Islands']);
        DB::table('countries')->insert(['iso' => 'lc', 'country' => 'Saint Lucia']);
        DB::table('countries')->insert(['iso' => 'mf', 'country' => 'Saint Martin (french)']);
        DB::table('countries')->insert(['iso' => 'mq', 'country' => 'Martinique']);
        DB::table('countries')->insert(['iso' => 'ms', 'country' => 'Montserrat']);
        DB::table('countries')->insert(['iso' => 'mx', 'country' => 'Mexico']);
        DB::table('countries')->insert(['iso' => 'pa', 'country' => 'Panama']);
        DB::table('countries')->insert(['iso' => 'pm', 'country' => 'Saint Pierre and Miquelon']);
        DB::table('countries')->insert(['iso' => 'pr', 'country' => 'Puerto Rico']);
        DB::table('countries')->insert(['iso' => 'sv', 'country' => 'El Salvador']);
        DB::table('countries')->insert(['iso' => 'sx', 'country' => 'Sint Maarten']);
        DB::table('countries')->insert(['iso' => 'tc', 'country' => 'Turks and Caicos Islands']);
        DB::table('countries')->insert(['iso' => 'tt', 'country' => 'Trinidad and Tobago']);
        DB::table('countries')->insert(['iso' => 'vc', 'country' => 'Saint Vincent and the Grenadines']);
        DB::table('countries')->insert(['iso' => 'vg', 'country' => 'Virgin Islands - British']);
        DB::table('countries')->insert(['iso' => 'vi', 'country' => 'Virgin Islands US']);
        DB::table('countries')->insert(['iso' => 'as', 'country' => 'American Samoa']);
        DB::table('countries')->insert(['iso' => 'au', 'country' => 'Australia']);
        DB::table('countries')->insert(['iso' => 'ck', 'country' => 'Cook Islands']);
        DB::table('countries')->insert(['iso' => 'fj', 'country' => 'Fiji']);
        DB::table('countries')->insert(['iso' => 'gu', 'country' => 'Guam']);
        DB::table('countries')->insert(['iso' => 'ki', 'country' => 'Kiribati']);
        DB::table('countries')->insert(['iso' => 'nf', 'country' => 'Norfolk Island']);
        DB::table('countries')->insert(['iso' => 'nr', 'country' => 'Nauru']);
        DB::table('countries')->insert(['iso' => 'nu', 'country' => 'Niue']);
        DB::table('countries')->insert(['iso' => 'nz', 'country' => 'New Zealand']);
        DB::table('countries')->insert(['iso' => 'pf', 'country' => 'French Polynesia']);
        DB::table('countries')->insert(['iso' => 'pg', 'country' => 'Papua New Guinea']);
        DB::table('countries')->insert(['iso' => 'pn', 'country' => 'Pitcairn']);
        DB::table('countries')->insert(['iso' => 'pw', 'country' => 'Palau']);
        DB::table('countries')->insert(['iso' => 'sb', 'country' => 'Solomon Islands']);
        DB::table('countries')->insert(['iso' => 'tk', 'country' => 'Tokelau']);
        DB::table('countries')->insert(['iso' => 'to', 'country' => 'Tonga']);
        DB::table('countries')->insert(['iso' => 'vu', 'country' => 'Vanuatu']);
        DB::table('countries')->insert(['iso' => 'wf', 'country' => 'Wallis and Futuna']);
        DB::table('countries')->insert(['iso' => 'ws', 'country' => 'Samoa']);
        DB::table('countries')->insert(['iso' => 'ar', 'country' => 'Argentina']);
        DB::table('countries')->insert(['iso' => 'bo', 'country' => 'Bolivia']);
        DB::table('countries')->insert(['iso' => 'br', 'country' => 'Brazil']);
        DB::table('countries')->insert(['iso' => 'cl', 'country' => 'Chile']);
        DB::table('countries')->insert(['iso' => 'co', 'country' => 'Colombia']);
        DB::table('countries')->insert(['iso' => 'ec', 'country' => 'Ecuador']);
        DB::table('countries')->insert(['iso' => 'fk', 'country' => 'Falkland Islands']);
        DB::table('countries')->insert(['iso' => 'gf', 'country' => 'French Guiana']);
        DB::table('countries')->insert(['iso' => 'gy', 'country' => 'Guyana']);
        DB::table('countries')->insert(['iso' => 'ni', 'country' => 'Nicaragua']);
        DB::table('countries')->insert(['iso' => 'pe', 'country' => 'Peru']);
        DB::table('countries')->insert(['iso' => 'py', 'country' => 'Paraguay']);
        DB::table('countries')->insert(['iso' => 'uy', 'country' => 'Uruguay']);
        DB::table('countries')->insert(['iso' => 've', 'country' => 'Venezuela']);
        DB::table('countries')->insert(['iso'=>'ae', 'country' => 'United Arab Emirates']);
    }

    public static function fixIso($iso)
    {
        switch ($iso) {
            case 'ct':
                // Cyprus
                return 'CY';
                break;
        }

        return $iso;
    }
}
