<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="me-1"> 🗓 </span>
        <span class="me-2">
          {{ $t('Timezone') }}
        </span>

        <help :url="$page.props.help_links.settings_preferences_timezone" :top="'5px'" />
      </h3>
      <pretty-button v-if="!editMode" :text="$t('Edit')" @click="enableEditMode" />
    </div>

    <!-- help text -->
    <div class="mb-6 flex rounded-xs border bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-slate-900">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6 pe-2"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>

      <div>
        <p>{{ $t('Regardless of where you are located in the world, have dates displayed in your own timezone.') }}</p>
      </div>
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="px-5 py-2">
        <span class="mb-2 block">{{ $t('Current timezone:') }}</span>
        <span class="mb-2 block rounded-xs bg-slate-100 px-5 py-2 text-sm dark:bg-slate-900">{{ localTimezone }}</span>
      </p>
    </div>

    <!-- edit mode -->
    <form
      v-if="editMode"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2 dark:border-gray-700">
        <errors :errors="form.errors" />

        <select
          v-model="form.timezone"
          name="timezone"
          class="rounded-md border-gray-300 bg-white px-3 py-2 pe-5 ps-2 shadow-xs focus:border-indigo-300 focus:outline-hidden focus:ring-3 focus:ring-indigo-200/50 ltr:bg-[right_3px_center] rtl:bg-[left_3px_center] dark:bg-gray-900 sm:text-sm">
          <optgroup label="General">
            <option value="GMT">GMT timezone</option>
            <option value="UTC">UTC timezone</option>
          </optgroup>
          <optgroup label="America">
            <option value="America/Adak">(GMT/UTC - 10:00) Adak</option>
            <option value="America/Anchorage">(GMT/UTC - 09:00) Anchorage</option>
            <option value="America/Anguilla">(GMT/UTC - 04:00) Anguilla</option>
            <option value="America/Antigua">(GMT/UTC - 04:00) Antigua</option>
            <option value="America/Araguaina">(GMT/UTC - 03:00) Araguaina</option>
            <option value="America/Argentina/Buenos_Aires">(GMT/UTC - 03:00) Argentina/Buenos Aires</option>
            <option value="America/Argentina/Catamarca">(GMT/UTC - 03:00) Argentina/Catamarca</option>
            <option value="America/Argentina/Cordoba">(GMT/UTC - 03:00) Argentina/Cordoba</option>
            <option value="America/Argentina/Jujuy">(GMT/UTC - 03:00) Argentina/Jujuy</option>
            <option value="America/Argentina/La_Rioja">(GMT/UTC - 03:00) Argentina/La Rioja</option>
            <option value="America/Argentina/Mendoza">(GMT/UTC - 03:00) Argentina/Mendoza</option>
            <option value="America/Argentina/Rio_Gallegos">(GMT/UTC - 03:00) Argentina/Rio Gallegos</option>
            <option value="America/Argentina/Salta">(GMT/UTC - 03:00) Argentina/Salta</option>
            <option value="America/Argentina/San_Juan">(GMT/UTC - 03:00) Argentina/San Juan</option>
            <option value="America/Argentina/San_Luis">(GMT/UTC - 03:00) Argentina/San Luis</option>
            <option value="America/Argentina/Tucuman">(GMT/UTC - 03:00) Argentina/Tucuman</option>
            <option value="America/Argentina/Ushuaia">(GMT/UTC - 03:00) Argentina/Ushuaia</option>
            <option value="America/Aruba">(GMT/UTC - 04:00) Aruba</option>
            <option value="America/Asuncion">(GMT/UTC - 03:00) Asuncion</option>
            <option value="America/Atikokan">(GMT/UTC - 05:00) Atikokan</option>
            <option value="America/Bahia">(GMT/UTC - 03:00) Bahia</option>
            <option value="America/Bahia_Banderas">(GMT/UTC - 06:00) Bahia Banderas</option>
            <option value="America/Barbados">(GMT/UTC - 04:00) Barbados</option>
            <option value="America/Belem">(GMT/UTC - 03:00) Belem</option>
            <option value="America/Belize">(GMT/UTC - 06:00) Belize</option>
            <option value="America/Blanc-Sablon">(GMT/UTC - 04:00) Blanc-Sablon</option>
            <option value="America/Boa_Vista">(GMT/UTC - 04:00) Boa Vista</option>
            <option value="America/Bogota">(GMT/UTC - 05:00) Bogota</option>
            <option value="America/Boise">(GMT/UTC - 07:00) Boise</option>
            <option value="America/Cambridge_Bay">(GMT/UTC - 07:00) Cambridge Bay</option>
            <option value="America/Campo_Grande">(GMT/UTC - 03:00) Campo Grande</option>
            <option value="America/Cancun">(GMT/UTC - 05:00) Cancun</option>
            <option value="America/Caracas">(GMT/UTC - 04:30) Caracas</option>
            <option value="America/Cayenne">(GMT/UTC - 03:00) Cayenne</option>
            <option value="America/Cayman">(GMT/UTC - 05:00) Cayman</option>
            <option value="America/Chicago">(GMT/UTC - 06:00) Chicago</option>
            <option value="America/Chihuahua">(GMT/UTC - 07:00) Chihuahua</option>
            <option value="America/Costa_Rica">(GMT/UTC - 06:00) Costa Rica</option>
            <option value="America/Creston">(GMT/UTC - 07:00) Creston</option>
            <option value="America/Cuiaba">(GMT/UTC - 03:00) Cuiaba</option>
            <option value="America/Curacao">(GMT/UTC - 04:00) Curacao</option>
            <option value="America/Danmarkshavn">(GMT/UTC + 00:00) Danmarkshavn</option>
            <option value="America/Dawson">(GMT/UTC - 08:00) Dawson</option>
            <option value="America/Dawson_Creek">(GMT/UTC - 07:00) Dawson Creek</option>
            <option value="America/Denver">(GMT/UTC - 07:00) Denver</option>
            <option value="America/Detroit">(GMT/UTC - 05:00) Detroit</option>
            <option value="America/Dominica">(GMT/UTC - 04:00) Dominica</option>
            <option value="America/Edmonton">(GMT/UTC - 07:00) Edmonton</option>
            <option value="America/Eirunepe">(GMT/UTC - 05:00) Eirunepe</option>
            <option value="America/El_Salvador">(GMT/UTC - 06:00) El Salvador</option>
            <option value="America/Fort_Nelson">(GMT/UTC - 07:00) Fort Nelson</option>
            <option value="America/Fortaleza">(GMT/UTC - 03:00) Fortaleza</option>
            <option value="America/Glace_Bay">(GMT/UTC - 04:00) Glace Bay</option>
            <option value="America/Godthab">(GMT/UTC - 03:00) Godthab</option>
            <option value="America/Goose_Bay">(GMT/UTC - 04:00) Goose Bay</option>
            <option value="America/Grand_Turk">(GMT/UTC - 04:00) Grand Turk</option>
            <option value="America/Grenada">(GMT/UTC - 04:00) Grenada</option>
            <option value="America/Guadeloupe">(GMT/UTC - 04:00) Guadeloupe</option>
            <option value="America/Guatemala">(GMT/UTC - 06:00) Guatemala</option>
            <option value="America/Guayaquil">(GMT/UTC - 05:00) Guayaquil</option>
            <option value="America/Guyana">(GMT/UTC - 04:00) Guyana</option>
            <option value="America/Halifax">(GMT/UTC - 04:00) Halifax</option>
            <option value="America/Havana">(GMT/UTC - 05:00) Havana</option>
            <option value="America/Hermosillo">(GMT/UTC - 07:00) Hermosillo</option>
            <option value="America/Indiana/Indianapolis">(GMT/UTC - 05:00) Indiana/Indianapolis</option>
            <option value="America/Indiana/Knox">(GMT/UTC - 06:00) Indiana/Knox</option>
            <option value="America/Indiana/Marengo">(GMT/UTC - 05:00) Indiana/Marengo</option>
            <option value="America/Indiana/Petersburg">(GMT/UTC - 05:00) Indiana/Petersburg</option>
            <option value="America/Indiana/Tell_City">(GMT/UTC - 06:00) Indiana/Tell City</option>
            <option value="America/Indiana/Vevay">(GMT/UTC - 05:00) Indiana/Vevay</option>
            <option value="America/Indiana/Vincennes">(GMT/UTC - 05:00) Indiana/Vincennes</option>
            <option value="America/Indiana/Winamac">(GMT/UTC - 05:00) Indiana/Winamac</option>
            <option value="America/Inuvik">(GMT/UTC - 07:00) Inuvik</option>
            <option value="America/Iqaluit">(GMT/UTC - 05:00) Iqaluit</option>
            <option value="America/Jamaica">(GMT/UTC - 05:00) Jamaica</option>
            <option value="America/Juneau">(GMT/UTC - 09:00) Juneau</option>
            <option value="America/Kentucky/Louisville">(GMT/UTC - 05:00) Kentucky/Louisville</option>
            <option value="America/Kentucky/Monticello">(GMT/UTC - 05:00) Kentucky/Monticello</option>
            <option value="America/Kralendijk">(GMT/UTC - 04:00) Kralendijk</option>
            <option value="America/La_Paz">(GMT/UTC - 04:00) La Paz</option>
            <option value="America/Lima">(GMT/UTC - 05:00) Lima</option>
            <option value="America/Los_Angeles">(GMT/UTC - 08:00) Los Angeles</option>
            <option value="America/Lower_Princes">(GMT/UTC - 04:00) Lower Princes</option>
            <option value="America/Maceio">(GMT/UTC - 03:00) Maceio</option>
            <option value="America/Managua">(GMT/UTC - 06:00) Managua</option>
            <option value="America/Manaus">(GMT/UTC - 04:00) Manaus</option>
            <option value="America/Marigot">(GMT/UTC - 04:00) Marigot</option>
            <option value="America/Martinique">(GMT/UTC - 04:00) Martinique</option>
            <option value="America/Matamoros">(GMT/UTC - 06:00) Matamoros</option>
            <option value="America/Mazatlan">(GMT/UTC - 07:00) Mazatlan</option>
            <option value="America/Menominee">(GMT/UTC - 06:00) Menominee</option>
            <option value="America/Merida">(GMT/UTC - 06:00) Merida</option>
            <option value="America/Metlakatla">(GMT/UTC - 09:00) Metlakatla</option>
            <option value="America/Mexico_City">(GMT/UTC - 06:00) Mexico City</option>
            <option value="America/Miquelon">(GMT/UTC - 03:00) Miquelon</option>
            <option value="America/Moncton">(GMT/UTC - 04:00) Moncton</option>
            <option value="America/Monterrey">(GMT/UTC - 06:00) Monterrey</option>
            <option value="America/Montevideo">(GMT/UTC - 03:00) Montevideo</option>
            <option value="America/Montserrat">(GMT/UTC - 04:00) Montserrat</option>
            <option value="America/Nassau">(GMT/UTC - 05:00) Nassau</option>
            <option value="America/New_York">(GMT/UTC - 05:00) New York</option>
            <option value="America/Nipigon">(GMT/UTC - 05:00) Nipigon</option>
            <option value="America/Nome">(GMT/UTC - 09:00) Nome</option>
            <option value="America/Noronha">(GMT/UTC - 02:00) Noronha</option>
            <option value="America/North_Dakota/Beulah">(GMT/UTC - 06:00) North Dakota/Beulah</option>
            <option value="America/North_Dakota/Center">(GMT/UTC - 06:00) North Dakota/Center</option>
            <option value="America/North_Dakota/New_Salem">(GMT/UTC - 06:00) North Dakota/New Salem</option>
            <option value="America/Ojinaga">(GMT/UTC - 07:00) Ojinaga</option>
            <option value="America/Panama">(GMT/UTC - 05:00) Panama</option>
            <option value="America/Pangnirtung">(GMT/UTC - 05:00) Pangnirtung</option>
            <option value="America/Paramaribo">(GMT/UTC - 03:00) Paramaribo</option>
            <option value="America/Phoenix">(GMT/UTC - 07:00) Phoenix</option>
            <option value="America/Port-au-Prince">(GMT/UTC - 05:00) Port-au-Prince</option>
            <option value="America/Port_of_Spain">(GMT/UTC - 04:00) Port of Spain</option>
            <option value="America/Porto_Velho">(GMT/UTC - 04:00) Porto Velho</option>
            <option value="America/Puerto_Rico">(GMT/UTC - 04:00) Puerto Rico</option>
            <option value="America/Rainy_River">(GMT/UTC - 06:00) Rainy River</option>
            <option value="America/Rankin_Inlet">(GMT/UTC - 06:00) Rankin Inlet</option>
            <option value="America/Recife">(GMT/UTC - 03:00) Recife</option>
            <option value="America/Regina">(GMT/UTC - 06:00) Regina</option>
            <option value="America/Resolute">(GMT/UTC - 06:00) Resolute</option>
            <option value="America/Rio_Branco">(GMT/UTC - 05:00) Rio Branco</option>
            <option value="America/Santarem">(GMT/UTC - 03:00) Santarem</option>
            <option value="America/Santiago">(GMT/UTC - 03:00) Santiago</option>
            <option value="America/Santo_Domingo">(GMT/UTC - 04:00) Santo Domingo</option>
            <option value="America/Sao_Paulo">(GMT/UTC - 02:00) Sao Paulo</option>
            <option value="America/Scoresbysund">(GMT/UTC - 01:00) Scoresbysund</option>
            <option value="America/Sitka">(GMT/UTC - 09:00) Sitka</option>
            <option value="America/St_Barthelemy">(GMT/UTC - 04:00) St. Barthelemy</option>
            <option value="America/St_Johns">(GMT/UTC - 03:30) St. Johns</option>
            <option value="America/St_Kitts">(GMT/UTC - 04:00) St. Kitts</option>
            <option value="America/St_Lucia">(GMT/UTC - 04:00) St. Lucia</option>
            <option value="America/St_Thomas">(GMT/UTC - 04:00) St. Thomas</option>
            <option value="America/St_Vincent">(GMT/UTC - 04:00) St. Vincent</option>
            <option value="America/Swift_Current">(GMT/UTC - 06:00) Swift Current</option>
            <option value="America/Tegucigalpa">(GMT/UTC - 06:00) Tegucigalpa</option>
            <option value="America/Thule">(GMT/UTC - 04:00) Thule</option>
            <option value="America/Thunder_Bay">(GMT/UTC - 05:00) Thunder Bay</option>
            <option value="America/Tijuana">(GMT/UTC - 08:00) Tijuana</option>
            <option value="America/Toronto">(GMT/UTC - 05:00) Toronto</option>
            <option value="America/Tortola">(GMT/UTC - 04:00) Tortola</option>
            <option value="America/Vancouver">(GMT/UTC - 08:00) Vancouver</option>
            <option value="America/Whitehorse">(GMT/UTC - 08:00) Whitehorse</option>
            <option value="America/Winnipeg">(GMT/UTC - 06:00) Winnipeg</option>
            <option value="America/Yakutat">(GMT/UTC - 09:00) Yakutat</option>
            <option value="America/Yellowknife">(GMT/UTC - 07:00) Yellowknife</option>
          </optgroup>
          <optgroup label="Europe">
            <option value="Europe/Amsterdam">(GMT/UTC + 01:00) Amsterdam</option>
            <option value="Europe/Andorra">(GMT/UTC + 01:00) Andorra</option>
            <option value="Europe/Astrakhan">(GMT/UTC + 04:00) Astrakhan</option>
            <option value="Europe/Athens">(GMT/UTC + 02:00) Athens</option>
            <option value="Europe/Belgrade">(GMT/UTC + 01:00) Belgrade</option>
            <option value="Europe/Berlin">(GMT/UTC + 01:00) Berlin</option>
            <option value="Europe/Bratislava">(GMT/UTC + 01:00) Bratislava</option>
            <option value="Europe/Brussels">(GMT/UTC + 01:00) Brussels</option>
            <option value="Europe/Bucharest">(GMT/UTC + 02:00) Bucharest</option>
            <option value="Europe/Budapest">(GMT/UTC + 01:00) Budapest</option>
            <option value="Europe/Busingen">(GMT/UTC + 01:00) Busingen</option>
            <option value="Europe/Chisinau">(GMT/UTC + 02:00) Chisinau</option>
            <option value="Europe/Copenhagen">(GMT/UTC + 01:00) Copenhagen</option>
            <option value="Europe/Dublin">(GMT/UTC + 00:00) Dublin</option>
            <option value="Europe/Gibraltar">(GMT/UTC + 01:00) Gibraltar</option>
            <option value="Europe/Guernsey">(GMT/UTC + 00:00) Guernsey</option>
            <option value="Europe/Helsinki">(GMT/UTC + 02:00) Helsinki</option>
            <option value="Europe/Isle_of_Man">(GMT/UTC + 00:00) Isle of Man</option>
            <option value="Europe/Istanbul">(GMT/UTC + 02:00) Istanbul</option>
            <option value="Europe/Jersey">(GMT/UTC + 00:00) Jersey</option>
            <option value="Europe/Kaliningrad">(GMT/UTC + 02:00) Kaliningrad</option>
            <option value="Europe/Kiev">(GMT/UTC + 02:00) Kiev</option>
            <option value="Europe/Lisbon">(GMT/UTC + 00:00) Lisbon</option>
            <option value="Europe/Ljubljana">(GMT/UTC + 01:00) Ljubljana</option>
            <option value="Europe/London">(GMT/UTC + 00:00) London</option>
            <option value="Europe/Luxembourg">(GMT/UTC + 01:00) Luxembourg</option>
            <option value="Europe/Madrid">(GMT/UTC + 01:00) Madrid</option>
            <option value="Europe/Malta">(GMT/UTC + 01:00) Malta</option>
            <option value="Europe/Mariehamn">(GMT/UTC + 02:00) Mariehamn</option>
            <option value="Europe/Minsk">(GMT/UTC + 03:00) Minsk</option>
            <option value="Europe/Monaco">(GMT/UTC + 01:00) Monaco</option>
            <option value="Europe/Moscow">(GMT/UTC + 03:00) Moscow</option>
            <option value="Europe/Oslo">(GMT/UTC + 01:00) Oslo</option>
            <option value="Europe/Paris">(GMT/UTC + 01:00) Paris</option>
            <option value="Europe/Podgorica">(GMT/UTC + 01:00) Podgorica</option>
            <option value="Europe/Prague">(GMT/UTC + 01:00) Prague</option>
            <option value="Europe/Riga">(GMT/UTC + 02:00) Riga</option>
            <option value="Europe/Rome">(GMT/UTC + 01:00) Rome</option>
            <option value="Europe/Samara">(GMT/UTC + 04:00) Samara</option>
            <option value="Europe/San_Marino">(GMT/UTC + 01:00) San Marino</option>
            <option value="Europe/Sarajevo">(GMT/UTC + 01:00) Sarajevo</option>
            <option value="Europe/Simferopol">(GMT/UTC + 03:00) Simferopol</option>
            <option value="Europe/Skopje">(GMT/UTC + 01:00) Skopje</option>
            <option value="Europe/Sofia">(GMT/UTC + 02:00) Sofia</option>
            <option value="Europe/Stockholm">(GMT/UTC + 01:00) Stockholm</option>
            <option value="Europe/Tallinn">(GMT/UTC + 02:00) Tallinn</option>
            <option value="Europe/Tirane">(GMT/UTC + 01:00) Tirane</option>
            <option value="Europe/Ulyanovsk">(GMT/UTC + 04:00) Ulyanovsk</option>
            <option value="Europe/Uzhgorod">(GMT/UTC + 02:00) Uzhgorod</option>
            <option value="Europe/Vaduz">(GMT/UTC + 01:00) Vaduz</option>
            <option value="Europe/Vatican">(GMT/UTC + 01:00) Vatican</option>
            <option value="Europe/Vienna">(GMT/UTC + 01:00) Vienna</option>
            <option value="Europe/Vilnius">(GMT/UTC + 02:00) Vilnius</option>
            <option value="Europe/Volgograd">(GMT/UTC + 03:00) Volgograd</option>
            <option value="Europe/Warsaw">(GMT/UTC + 01:00) Warsaw</option>
            <option value="Europe/Zagreb">(GMT/UTC + 01:00) Zagreb</option>
            <option value="Europe/Zaporozhye">(GMT/UTC + 02:00) Zaporozhye</option>
            <option value="Europe/Zurich">(GMT/UTC + 01:00) Zurich</option>
          </optgroup>
          <optgroup label="Africa">
            <option value="Africa/Abidjan">(GMT/UTC + 00:00) Abidjan</option>
            <option value="Africa/Accra">(GMT/UTC + 00:00) Accra</option>
            <option value="Africa/Addis_Ababa">(GMT/UTC + 03:00) Addis Ababa</option>
            <option value="Africa/Algiers">(GMT/UTC + 01:00) Algiers</option>
            <option value="Africa/Asmara">(GMT/UTC + 03:00) Asmara</option>
            <option value="Africa/Bamako">(GMT/UTC + 00:00) Bamako</option>
            <option value="Africa/Bangui">(GMT/UTC + 01:00) Bangui</option>
            <option value="Africa/Banjul">(GMT/UTC + 00:00) Banjul</option>
            <option value="Africa/Bissau">(GMT/UTC + 00:00) Bissau</option>
            <option value="Africa/Blantyre">(GMT/UTC + 02:00) Blantyre</option>
            <option value="Africa/Brazzaville">(GMT/UTC + 01:00) Brazzaville</option>
            <option value="Africa/Bujumbura">(GMT/UTC + 02:00) Bujumbura</option>
            <option value="Africa/Cairo">(GMT/UTC + 02:00) Cairo</option>
            <option value="Africa/Casablanca">(GMT/UTC + 00:00) Casablanca</option>
            <option value="Africa/Ceuta">(GMT/UTC + 01:00) Ceuta</option>
            <option value="Africa/Conakry">(GMT/UTC + 00:00) Conakry</option>
            <option value="Africa/Dakar">(GMT/UTC + 00:00) Dakar</option>
            <option value="Africa/Dar_es_Salaam">(GMT/UTC + 03:00) Dar es Salaam</option>
            <option value="Africa/Djibouti">(GMT/UTC + 03:00) Djibouti</option>
            <option value="Africa/Douala">(GMT/UTC + 01:00) Douala</option>
            <option value="Africa/El_Aaiun">(GMT/UTC + 00:00) El Aaiun</option>
            <option value="Africa/Freetown">(GMT/UTC + 00:00) Freetown</option>
            <option value="Africa/Gaborone">(GMT/UTC + 02:00) Gaborone</option>
            <option value="Africa/Harare">(GMT/UTC + 02:00) Harare</option>
            <option value="Africa/Johannesburg">(GMT/UTC + 02:00) Johannesburg</option>
            <option value="Africa/Juba">(GMT/UTC + 03:00) Juba</option>
            <option value="Africa/Kampala">(GMT/UTC + 03:00) Kampala</option>
            <option value="Africa/Khartoum">(GMT/UTC + 03:00) Khartoum</option>
            <option value="Africa/Kigali">(GMT/UTC + 02:00) Kigali</option>
            <option value="Africa/Kinshasa">(GMT/UTC + 01:00) Kinshasa</option>
            <option value="Africa/Lagos">(GMT/UTC + 01:00) Lagos</option>
            <option value="Africa/Libreville">(GMT/UTC + 01:00) Libreville</option>
            <option value="Africa/Lome">(GMT/UTC + 00:00) Lome</option>
            <option value="Africa/Luanda">(GMT/UTC + 01:00) Luanda</option>
            <option value="Africa/Lubumbashi">(GMT/UTC + 02:00) Lubumbashi</option>
            <option value="Africa/Lusaka">(GMT/UTC + 02:00) Lusaka</option>
            <option value="Africa/Malabo">(GMT/UTC + 01:00) Malabo</option>
            <option value="Africa/Maputo">(GMT/UTC + 02:00) Maputo</option>
            <option value="Africa/Maseru">(GMT/UTC + 02:00) Maseru</option>
            <option value="Africa/Mbabane">(GMT/UTC + 02:00) Mbabane</option>
            <option value="Africa/Mogadishu">(GMT/UTC + 03:00) Mogadishu</option>
            <option value="Africa/Monrovia">(GMT/UTC + 00:00) Monrovia</option>
            <option value="Africa/Nairobi">(GMT/UTC + 03:00) Nairobi</option>
            <option value="Africa/Ndjamena">(GMT/UTC + 01:00) Ndjamena</option>
            <option value="Africa/Niamey">(GMT/UTC + 01:00) Niamey</option>
            <option value="Africa/Nouakchott">(GMT/UTC + 00:00) Nouakchott</option>
            <option value="Africa/Ouagadougou">(GMT/UTC + 00:00) Ouagadougou</option>
            <option value="Africa/Porto-Novo">(GMT/UTC + 01:00) Porto-Novo</option>
            <option value="Africa/Sao_Tome">(GMT/UTC + 00:00) Sao Tome</option>
            <option value="Africa/Tripoli">(GMT/UTC + 02:00) Tripoli</option>
            <option value="Africa/Tunis">(GMT/UTC + 01:00) Tunis</option>
            <option value="Africa/Windhoek">(GMT/UTC + 02:00) Windhoek</option>
          </optgroup>
          <optgroup label="Antarctica">
            <option value="Antarctica/Casey">(GMT/UTC + 08:00) Casey</option>
            <option value="Antarctica/Davis">(GMT/UTC + 07:00) Davis</option>
            <option value="Antarctica/DumontDUrville">(GMT/UTC + 10:00) DumontDUrville</option>
            <option value="Antarctica/Macquarie">(GMT/UTC + 11:00) Macquarie</option>
            <option value="Antarctica/Mawson">(GMT/UTC + 05:00) Mawson</option>
            <option value="Antarctica/McMurdo">(GMT/UTC + 13:00) McMurdo</option>
            <option value="Antarctica/Palmer">(GMT/UTC - 03:00) Palmer</option>
            <option value="Antarctica/Rothera">(GMT/UTC - 03:00) Rothera</option>
            <option value="Antarctica/Syowa">(GMT/UTC + 03:00) Syowa</option>
            <option value="Antarctica/Troll">(GMT/UTC + 00:00) Troll</option>
            <option value="Antarctica/Vostok">(GMT/UTC + 06:00) Vostok</option>
          </optgroup>
          <optgroup label="Arctic">
            <option value="Arctic/Longyearbyen">(GMT/UTC + 01:00) Longyearbyen</option>
          </optgroup>
          <optgroup label="Asia">
            <option value="Asia/Aden">(GMT/UTC + 03:00) Aden</option>
            <option value="Asia/Almaty">(GMT/UTC + 06:00) Almaty</option>
            <option value="Asia/Amman">(GMT/UTC + 02:00) Amman</option>
            <option value="Asia/Anadyr">(GMT/UTC + 12:00) Anadyr</option>
            <option value="Asia/Aqtau">(GMT/UTC + 05:00) Aqtau</option>
            <option value="Asia/Aqtobe">(GMT/UTC + 05:00) Aqtobe</option>
            <option value="Asia/Ashgabat">(GMT/UTC + 05:00) Ashgabat</option>
            <option value="Asia/Baghdad">(GMT/UTC + 03:00) Baghdad</option>
            <option value="Asia/Bahrain">(GMT/UTC + 03:00) Bahrain</option>
            <option value="Asia/Baku">(GMT/UTC + 04:00) Baku</option>
            <option value="Asia/Bangkok">(GMT/UTC + 07:00) Bangkok</option>
            <option value="Asia/Barnaul">(GMT/UTC + 07:00) Barnaul</option>
            <option value="Asia/Beirut">(GMT/UTC + 02:00) Beirut</option>
            <option value="Asia/Bishkek">(GMT/UTC + 06:00) Bishkek</option>
            <option value="Asia/Brunei">(GMT/UTC + 08:00) Brunei</option>
            <option value="Asia/Chita">(GMT/UTC + 09:00) Chita</option>
            <option value="Asia/Choibalsan">(GMT/UTC + 08:00) Choibalsan</option>
            <option value="Asia/Colombo">(GMT/UTC + 05:30) Colombo</option>
            <option value="Asia/Damascus">(GMT/UTC + 02:00) Damascus</option>
            <option value="Asia/Dhaka">(GMT/UTC + 06:00) Dhaka</option>
            <option value="Asia/Dili">(GMT/UTC + 09:00) Dili</option>
            <option value="Asia/Dubai">(GMT/UTC + 04:00) Dubai</option>
            <option value="Asia/Dushanbe">(GMT/UTC + 05:00) Dushanbe</option>
            <option value="Asia/Gaza">(GMT/UTC + 02:00) Gaza</option>
            <option value="Asia/Hebron">(GMT/UTC + 02:00) Hebron</option>
            <option value="Asia/Ho_Chi_Minh">(GMT/UTC + 07:00) Ho Chi Minh</option>
            <option value="Asia/Hong_Kong">(GMT/UTC + 08:00) Hong Kong</option>
            <option value="Asia/Hovd">(GMT/UTC + 07:00) Hovd</option>
            <option value="Asia/Irkutsk">(GMT/UTC + 08:00) Irkutsk</option>
            <option value="Asia/Jakarta">(GMT/UTC + 07:00) Jakarta</option>
            <option value="Asia/Jayapura">(GMT/UTC + 09:00) Jayapura</option>
            <option value="Asia/Jerusalem">(GMT/UTC + 02:00) Jerusalem</option>
            <option value="Asia/Kabul">(GMT/UTC + 04:30) Kabul</option>
            <option value="Asia/Kamchatka">(GMT/UTC + 12:00) Kamchatka</option>
            <option value="Asia/Karachi">(GMT/UTC + 05:00) Karachi</option>
            <option value="Asia/Kathmandu">(GMT/UTC + 05:45) Kathmandu</option>
            <option value="Asia/Khandyga">(GMT/UTC + 09:00) Khandyga</option>
            <option value="Asia/Kolkata">(GMT/UTC + 05:30) Kolkata</option>
            <option value="Asia/Krasnoyarsk">(GMT/UTC + 07:00) Krasnoyarsk</option>
            <option value="Asia/Kuala_Lumpur">(GMT/UTC + 08:00) Kuala Lumpur</option>
            <option value="Asia/Kuching">(GMT/UTC + 08:00) Kuching</option>
            <option value="Asia/Kuwait">(GMT/UTC + 03:00) Kuwait</option>
            <option value="Asia/Macau">(GMT/UTC + 08:00) Macau</option>
            <option value="Asia/Magadan">(GMT/UTC + 10:00) Magadan</option>
            <option value="Asia/Makassar">(GMT/UTC + 08:00) Makassar</option>
            <option value="Asia/Manila">(GMT/UTC + 08:00) Manila</option>
            <option value="Asia/Muscat">(GMT/UTC + 04:00) Muscat</option>
            <option value="Asia/Nicosia">(GMT/UTC + 02:00) Nicosia</option>
            <option value="Asia/Novokuznetsk">(GMT/UTC + 07:00) Novokuznetsk</option>
            <option value="Asia/Novosibirsk">(GMT/UTC + 06:00) Novosibirsk</option>
            <option value="Asia/Omsk">(GMT/UTC + 06:00) Omsk</option>
            <option value="Asia/Oral">(GMT/UTC + 05:00) Oral</option>
            <option value="Asia/Phnom_Penh">(GMT/UTC + 07:00) Phnom Penh</option>
            <option value="Asia/Pontianak">(GMT/UTC + 07:00) Pontianak</option>
            <option value="Asia/Pyongyang">(GMT/UTC + 08:30) Pyongyang</option>
            <option value="Asia/Qatar">(GMT/UTC + 03:00) Qatar</option>
            <option value="Asia/Qyzylorda">(GMT/UTC + 06:00) Qyzylorda</option>
            <option value="Asia/Rangoon">(GMT/UTC + 06:30) Rangoon</option>
            <option value="Asia/Riyadh">(GMT/UTC + 03:00) Riyadh</option>
            <option value="Asia/Sakhalin">(GMT/UTC + 11:00) Sakhalin</option>
            <option value="Asia/Samarkand">(GMT/UTC + 05:00) Samarkand</option>
            <option value="Asia/Seoul">(GMT/UTC + 09:00) Seoul</option>
            <option value="Asia/Shanghai">(GMT/UTC + 08:00) Shanghai</option>
            <option value="Asia/Singapore">(GMT/UTC + 08:00) Singapore</option>
            <option value="Asia/Srednekolymsk">(GMT/UTC + 11:00) Srednekolymsk</option>
            <option value="Asia/Taipei">(GMT/UTC + 08:00) Taipei</option>
            <option value="Asia/Tashkent">(GMT/UTC + 05:00) Tashkent</option>
            <option value="Asia/Tbilisi">(GMT/UTC + 04:00) Tbilisi</option>
            <option value="Asia/Tehran">(GMT/UTC + 03:30) Tehran</option>
            <option value="Asia/Thimphu">(GMT/UTC + 06:00) Thimphu</option>
            <option value="Asia/Tokyo">(GMT/UTC + 09:00) Tokyo</option>
            <option value="Asia/Ulaanbaatar">(GMT/UTC + 08:00) Ulaanbaatar</option>
            <option value="Asia/Urumqi">(GMT/UTC + 06:00) Urumqi</option>
            <option value="Asia/Ust-Nera">(GMT/UTC + 10:00) Ust-Nera</option>
            <option value="Asia/Vientiane">(GMT/UTC + 07:00) Vientiane</option>
            <option value="Asia/Vladivostok">(GMT/UTC + 10:00) Vladivostok</option>
            <option value="Asia/Yakutsk">(GMT/UTC + 09:00) Yakutsk</option>
            <option value="Asia/Yekaterinburg">(GMT/UTC + 05:00) Yekaterinburg</option>
            <option value="Asia/Yerevan">(GMT/UTC + 04:00) Yerevan</option>
          </optgroup>
          <optgroup label="Atlantic">
            <option value="Atlantic/Azores">(GMT/UTC - 01:00) Azores</option>
            <option value="Atlantic/Bermuda">(GMT/UTC - 04:00) Bermuda</option>
            <option value="Atlantic/Canary">(GMT/UTC + 00:00) Canary</option>
            <option value="Atlantic/Cape_Verde">(GMT/UTC - 01:00) Cape Verde</option>
            <option value="Atlantic/Faroe">(GMT/UTC + 00:00) Faroe</option>
            <option value="Atlantic/Madeira">(GMT/UTC + 00:00) Madeira</option>
            <option value="Atlantic/Reykjavik">(GMT/UTC + 00:00) Reykjavik</option>
            <option value="Atlantic/South_Georgia">(GMT/UTC - 02:00) South Georgia</option>
            <option value="Atlantic/St_Helena">(GMT/UTC + 00:00) St. Helena</option>
            <option value="Atlantic/Stanley">(GMT/UTC - 03:00) Stanley</option>
          </optgroup>
          <optgroup label="Australia">
            <option value="Australia/Adelaide">(GMT/UTC + 10:30) Adelaide</option>
            <option value="Australia/Brisbane">(GMT/UTC + 10:00) Brisbane</option>
            <option value="Australia/Broken_Hill">(GMT/UTC + 10:30) Broken Hill</option>
            <option value="Australia/Currie">(GMT/UTC + 11:00) Currie</option>
            <option value="Australia/Darwin">(GMT/UTC + 09:30) Darwin</option>
            <option value="Australia/Eucla">(GMT/UTC + 08:45) Eucla</option>
            <option value="Australia/Hobart">(GMT/UTC + 11:00) Hobart</option>
            <option value="Australia/Lindeman">(GMT/UTC + 10:00) Lindeman</option>
            <option value="Australia/Lord_Howe">(GMT/UTC + 11:00) Lord Howe</option>
            <option value="Australia/Melbourne">(GMT/UTC + 11:00) Melbourne</option>
            <option value="Australia/Perth">(GMT/UTC + 08:00) Perth</option>
            <option value="Australia/Sydney">(GMT/UTC + 11:00) Sydney</option>
          </optgroup>
          <optgroup label="Indian">
            <option value="Indian/Antananarivo">(GMT/UTC + 03:00) Antananarivo</option>
            <option value="Indian/Chagos">(GMT/UTC + 06:00) Chagos</option>
            <option value="Indian/Christmas">(GMT/UTC + 07:00) Christmas</option>
            <option value="Indian/Cocos">(GMT/UTC + 06:30) Cocos</option>
            <option value="Indian/Comoro">(GMT/UTC + 03:00) Comoro</option>
            <option value="Indian/Kerguelen">(GMT/UTC + 05:00) Kerguelen</option>
            <option value="Indian/Mahe">(GMT/UTC + 04:00) Mahe</option>
            <option value="Indian/Maldives">(GMT/UTC + 05:00) Maldives</option>
            <option value="Indian/Mauritius">(GMT/UTC + 04:00) Mauritius</option>
            <option value="Indian/Mayotte">(GMT/UTC + 03:00) Mayotte</option>
            <option value="Indian/Reunion">(GMT/UTC + 04:00) Reunion</option>
          </optgroup>
          <optgroup label="Pacific">
            <option value="Pacific/Apia">(GMT/UTC + 14:00) Apia</option>
            <option value="Pacific/Auckland">(GMT/UTC + 13:00) Auckland</option>
            <option value="Pacific/Bougainville">(GMT/UTC + 11:00) Bougainville</option>
            <option value="Pacific/Chatham">(GMT/UTC + 13:45) Chatham</option>
            <option value="Pacific/Chuuk">(GMT/UTC + 10:00) Chuuk</option>
            <option value="Pacific/Easter">(GMT/UTC - 05:00) Easter</option>
            <option value="Pacific/Efate">(GMT/UTC + 11:00) Efate</option>
            <option value="Pacific/Enderbury">(GMT/UTC + 13:00) Enderbury</option>
            <option value="Pacific/Fakaofo">(GMT/UTC + 13:00) Fakaofo</option>
            <option value="Pacific/Fiji">(GMT/UTC + 12:00) Fiji</option>
            <option value="Pacific/Funafuti">(GMT/UTC + 12:00) Funafuti</option>
            <option value="Pacific/Galapagos">(GMT/UTC - 06:00) Galapagos</option>
            <option value="Pacific/Gambier">(GMT/UTC - 09:00) Gambier</option>
            <option value="Pacific/Guadalcanal">(GMT/UTC + 11:00) Guadalcanal</option>
            <option value="Pacific/Guam">(GMT/UTC + 10:00) Guam</option>
            <option value="Pacific/Honolulu">(GMT/UTC - 10:00) Honolulu</option>
            <option value="Pacific/Johnston">(GMT/UTC - 10:00) Johnston</option>
            <option value="Pacific/Kiritimati">(GMT/UTC + 14:00) Kiritimati</option>
            <option value="Pacific/Kosrae">(GMT/UTC + 11:00) Kosrae</option>
            <option value="Pacific/Kwajalein">(GMT/UTC + 12:00) Kwajalein</option>
            <option value="Pacific/Majuro">(GMT/UTC + 12:00) Majuro</option>
            <option value="Pacific/Marquesas">(GMT/UTC - 09:30) Marquesas</option>
            <option value="Pacific/Midway">(GMT/UTC - 11:00) Midway</option>
            <option value="Pacific/Nauru">(GMT/UTC + 12:00) Nauru</option>
            <option value="Pacific/Niue">(GMT/UTC - 11:00) Niue</option>
            <option value="Pacific/Norfolk">(GMT/UTC + 11:00) Norfolk</option>
            <option value="Pacific/Noumea">(GMT/UTC + 11:00) Noumea</option>
            <option value="Pacific/Pago_Pago">(GMT/UTC - 11:00) Pago Pago</option>
            <option value="Pacific/Palau">(GMT/UTC + 09:00) Palau</option>
            <option value="Pacific/Pitcairn">(GMT/UTC - 08:00) Pitcairn</option>
            <option value="Pacific/Pohnpei">(GMT/UTC + 11:00) Pohnpei</option>
            <option value="Pacific/Port_Moresby">(GMT/UTC + 10:00) Port Moresby</option>
            <option value="Pacific/Rarotonga">(GMT/UTC - 10:00) Rarotonga</option>
            <option value="Pacific/Saipan">(GMT/UTC + 10:00) Saipan</option>
            <option value="Pacific/Tahiti">(GMT/UTC - 10:00) Tahiti</option>
            <option value="Pacific/Tarawa">(GMT/UTC + 12:00) Tarawa</option>
            <option value="Pacific/Tongatapu">(GMT/UTC + 13:00) Tongatapu</option>
            <option value="Pacific/Wake">(GMT/UTC + 12:00) Wake</option>
            <option value="Pacific/Wallis">(GMT/UTC + 12:00) Wallis</option>
          </optgroup>
        </select>
      </div>

      <!-- actions -->
      <div class="flex justify-between p-5">
        <pretty-link :text="$t('Cancel')" :class="'me-3'" @click="editMode = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Help from '@/Shared/Help.vue';

export default {
  components: {
    PrettyButton,
    PrettyLink,
    Errors,
    Help,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      editMode: false,
      localTimezone: '',
      form: {
        timezone: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localTimezone = this.data.timezone;
    this.form.timezone = this.data.timezone;
  },

  methods: {
    enableEditMode() {
      this.editMode = true;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('Changes saved'), 'success');
          this.localTimezone = response.data.data.timezone;
          this.editMode = false;
          this.loadingState = null;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
