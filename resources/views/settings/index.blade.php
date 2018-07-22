@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ auth()->user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12">
          <ul class="horizontal">
            <li>
              <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_settings') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ auth()->user()->getFluidLayout() }} mb4">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-md-9">
        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            @include('partials.errors')

            @if (session('status'))
              <div class="alert alert-success">
                  {{ session('status') }}
              </div>
            @endif

            <form action="{{ route('settings.save') }}" method="POST">
              {{ csrf_field() }}

              {{-- id --}}
              <input type="hidden" name="id" value="{{ auth()->user()->id }}" />

              {{-- names --}}
              <div class="form-group">
                <label for="firstname">{{ trans('settings.firstname') }}</label>
                <input type="text" class="form-control" name="first_name" id="first_name" required value="{{ auth()->user()->first_name }}">
              </div>

              <div class="form-group">
                <label for="firstname">{{ trans('settings.lastname') }}</label>
                <input type="text" class="form-control" name="last_name" id="last_name" required value="{{ auth()->user()->last_name }}">
              </div>

              {{-- email address --}}
              <div class="form-group">
                <label for="email">{{ trans('settings.email') }}</label>
                <input type="email" class="form-control" name="email" id="email" placeholder="{{ trans('settings.email_placeholder') }}" required value="{{ auth()->user()->email }}">
                <small id="emailHelp" class="form-text text-muted">{{ trans('settings.email_help') }}</small>
              </div>

              {{-- Locale --}}
              <div class="form-group">
                <label for="locale">{{ trans('settings.locale') }}</label>
                <select class="form-control" name="locale" id="locale">
                  @foreach($locales as $locale)
                    <option value="{{ $locale['lang'] }}" {{ (auth()->user()->locale == $locale['lang'])?'selected':'' }}>{{ $locale['name'] }}</option>
                  @endforeach
                </select>
              </div>

              {{-- currency for user --}}
              <div class="form-group">
                <label for="layout">{{ trans('settings.currency') }}</label>
                @include('partials.components.currency-select', ['selectionID' => auth()->user()->currency_id ])
              </div>

              {{-- Way of displaying names --}}
              <div class="form-group">
                <label for="name_order">{{ trans('settings.name_order') }}</label>
                <select name="name_order" class="form-control">
                  @foreach ($namesOrder as $nameOrder)
                  <option value="{{ $nameOrder }}" {{ (auth()->user()->name_order == $nameOrder) ? 'selected':'' }}>{{ trans('settings.name_order_'.$nameOrder) }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Timezone --}}
              <div class="form-group">
                <label for="timezone">{{ trans('settings.timezone') }}</label>
                <select class="form-control" name="timezone" id="timezone">
                  <option value='US/Eastern' {{ (DateHelper::getTimezone() == 'US/Eastern')?'selected':'' }}>(UTC-05:00) Montreal/New-York</option>
                  <option value='US/Central' {{ (DateHelper::getTimezone() == 'US/Central')?'selected':'' }}>(UTC-06:00) Central Time (US &amp; Canada)</option>
                  <option value='America/Los_Angeles' {{ (DateHelper::getTimezone() == 'America/Los_Angeles')?'selected':'' }}>(UTC-08:00) Pacific Time (US &amp; Canada)</option>
                  <option value='Pacific/Midway' {{ (DateHelper::getTimezone() == 'Pacific/Midway')?'selected':'' }}>(UTC-11:00) Midway Island</option>
                  <option value='Pacific/Samoa' {{ (DateHelper::getTimezone() == 'Pacific/Samoa')?'selected':'' }}>(UTC-11:00) Samoa</option>
                  <option value='Pacific/Honolulu' {{ (DateHelper::getTimezone() == 'Pacific/Honolulu')?'selected':'' }}>(UTC-10:00) Hawaii</option>
                  <option value='US/Alaska' {{ (DateHelper::getTimezone() == 'US/Alaska')?'selected':'' }}>(UTC-09:00) Alaska</option>
                  <option value='America/Tijuana' {{ (DateHelper::getTimezone() == 'America/Tijuana')?'selected':'' }}>(UTC-08:00) Tijuana</option>
                  <option value='US/Arizona' {{ (DateHelper::getTimezone() == 'US/Arizona')?'selected':'' }}>(UTC-07:00) Arizona</option>
                  <option value='America/Chihuahua' {{ (DateHelper::getTimezone() == 'America/Chihuahua')?'selected':'' }}>(UTC-07:00) Chihuahua</option>
                  <option value='America/Chihuahua' {{ (DateHelper::getTimezone() == 'America/Chihuahua')?'selected':'' }}>(UTC-07:00) La Paz</option>
                  <option value='America/Mazatlan' {{ (DateHelper::getTimezone() == 'America/Mazatlan')?'selected':'' }}>(UTC-07:00) Mazatlan</option>
                  <option value='US/Mountain' {{ (DateHelper::getTimezone() == 'US/Mountain')?'selected':'' }}>(UTC-07:00) Mountain Time (US &amp; Canada)</option>
                  <option value='America/Managua' {{ (DateHelper::getTimezone() == 'America/Managua')?'selected':'' }}>(UTC-06:00) Central America</option>
                  <option value='US/Central' {{ (DateHelper::getTimezone() == 'US/Central')?'selected':'' }}>(UTC-06:00) Central Time (US &amp; Canada)</option>
                  <option value='America/Mexico_City' {{ (DateHelper::getTimezone() == 'America/Mexico_City')?'selected':'' }}>(UTC-06:00) Guadalajara</option>
                  <option value='America/Mexico_City' {{ (DateHelper::getTimezone() == 'America/Mexico_City')?'selected':'' }}>(UTC-06:00) Mexico City</option>
                  <option value='America/Monterrey' {{ (DateHelper::getTimezone() == 'America/Monterrey')?'selected':'' }}>(UTC-06:00) Monterrey</option>
                  <option value='Canada/Saskatchewan' {{ (DateHelper::getTimezone() == 'Canada/Saskatchewan')?'selected':'' }}>(UTC-06:00) Saskatchewan</option>
                  <option value='America/Bogota' {{ (DateHelper::getTimezone() == 'America/Bogota')?'selected':'' }}>(UTC-05:00) Bogota</option>
                  <option value='US/Eastern' {{ (DateHelper::getTimezone() == 'US/Eastern')?'selected':'' }}>(UTC-05:00) Eastern Time (US &amp; Canada)</option>
                  <option value='US/East-Indiana' {{ (DateHelper::getTimezone() == 'US/East')?'selected':'' }}>(UTC-05:00) Indiana (East)</option>
                  <option value='America/Lima' {{ (DateHelper::getTimezone() == 'America/Lima')?'selected':'' }}>(UTC-05:00) Lima</option>
                  <option value='America/Bogota' {{ (DateHelper::getTimezone() == 'America/Bogota')?'selected':'' }}>(UTC-05:00) Quito</option>
                  <option value='Canada/Atlantic' {{ (DateHelper::getTimezone() == 'Canada/Atlantic')?'selected':'' }}>(UTC-04:00) Atlantic Time (Canada)</option>
                  <option value='America/Caracas' {{ (DateHelper::getTimezone() == 'America/Caracas')?'selected':'' }}>(UTC-04:30) Caracas</option>
                  <option value='America/La_Paz' {{ (DateHelper::getTimezone() == 'America/La_Paz')?'selected':'' }}>(UTC-04:00) La Paz</option>
                  <option value='America/Santiago' {{ (DateHelper::getTimezone() == 'America/Santiago')?'selected':'' }}>(UTC-04:00) Santiago</option>
                  <option value='Canada/Newfoundland' {{ (DateHelper::getTimezone() == 'Canada/Newfoundland')?'selected':'' }}>(UTC-03:30) Newfoundland</option>
                  <option value='America/Sao_Paulo' {{ (DateHelper::getTimezone() == 'America/Sao_Paulo')?'selected':'' }}>(UTC-03:00) Brasilia</option>
                  <option value='America/Argentina/Buenos_Aires' {{ (DateHelper::getTimezone() == 'America/Argentina')?'selected':'' }}>(UTC-03:00) Buenos Aires</option>
                  <option value='America/Godthab' {{ (DateHelper::getTimezone() == 'America/Godthab')?'selected':'' }}>(UTC-03:00) Greenland</option>
                  <option value='America/Noronha' {{ (DateHelper::getTimezone() == 'America/Noronha')?'selected':'' }}>(UTC-02:00) Mid-Atlantic</option>
                  <option value='Atlantic/Azores' {{ (DateHelper::getTimezone() == 'Atlantic/Azores')?'selected':'' }}>(UTC-01:00) Azores</option>
                  <option value='Atlantic/Cape_Verde' {{ (DateHelper::getTimezone() == 'Atlantic/Cape_Verde')?'selected':'' }}>(UTC-01:00) Cape Verde Is.</option>
                  <option value='Africa/Casablanca' {{ (DateHelper::getTimezone() == 'Africa/Casablanca')?'selected':'' }}>(UTC+00:00) Casablanca</option>
                  <option value='Europe/London' {{ (DateHelper::getTimezone() == 'Europe/London')?'selected':'' }}>(UTC+00:00) Edinburgh</option>
                  <option value='Etc/Greenwich' {{ (DateHelper::getTimezone() == 'Etc/Greenwich')?'selected':'' }}>(UTC+00:00) Greenwich Mean Time : Dublin</option>
                  <option value='Europe/Lisbon' {{ (DateHelper::getTimezone() == 'Europe/Lisbon')?'selected':'' }}>(UTC+00:00) Lisbon</option>
                  <option value='Europe/London' {{ (DateHelper::getTimezone() == 'Europe/London')?'selected':'' }}>(UTC+00:00) London</option>
                  <option value='Africa/Monrovia' {{ (DateHelper::getTimezone() == 'Africa/Monrovia')?'selected':'' }}>(UTC+00:00) Monrovia</option>
                  <option value='UTC' {{ (DateHelper::getTimezone() == 'UTC')?'selected':'' }}>(UTC+00:00) UTC</option>
                  <option value='Europe/Amsterdam' {{ (DateHelper::getTimezone() == 'Europe/Amsterdam')?'selected':'' }}>(UTC+01:00) Amsterdam</option>
                  <option value='Europe/Belgrade' {{ (DateHelper::getTimezone() == 'Europe/Belgrade')?'selected':'' }}>(UTC+01:00) Belgrade</option>
                  <option value='Europe/Berlin' {{ (DateHelper::getTimezone() == 'Europe/Berlin')?'selected':'' }}>(UTC+01:00) Berlin</option>
                  <option value='Europe/Bratislava' {{ (DateHelper::getTimezone() == 'Europe/Bratislava')?'selected':'' }}>(UTC+01:00) Bratislava</option>
                  <option value='Europe/Brussels' {{ (DateHelper::getTimezone() == 'Europe/Brussels')?'selected':'' }}>(UTC+01:00) Brussels</option>
                  <option value='Europe/Budapest' {{ (DateHelper::getTimezone() == 'Europe/Budapest')?'selected':'' }}>(UTC+01:00) Budapest</option>
                  <option value='Europe/Copenhagen' {{ (DateHelper::getTimezone() == 'Europe/Copenhagen')?'selected':'' }}>(UTC+01:00) Copenhagen</option>
                  <option value='Europe/Ljubljana' {{ (DateHelper::getTimezone() == 'Europe/Ljubljana')?'selected':'' }}>(UTC+01:00) Ljubljana</option>
                  <option value='Europe/Madrid' {{ (DateHelper::getTimezone() == 'Europe/Madrid')?'selected':'' }}>(UTC+01:00) Madrid</option>
                  <option value='Europe/Paris' {{ (DateHelper::getTimezone() == 'Europe/Paris')?'selected':'' }}>(UTC+01:00) Paris</option>
                  <option value='Europe/Prague' {{ (DateHelper::getTimezone() == 'Europe/Prague')?'selected':'' }}>(UTC+01:00) Prague</option>
                  <option value='Europe/Rome' {{ (DateHelper::getTimezone() == 'Europe/Rome')?'selected':'' }}>(UTC+01:00) Rome</option>
                  <option value='Europe/Sarajevo' {{ (DateHelper::getTimezone() == 'Europe/Sarajevo')?'selected':'' }}>(UTC+01:00) Sarajevo</option>
                  <option value='Europe/Skopje' {{ (DateHelper::getTimezone() == 'Europe/Skopje')?'selected':'' }}>(UTC+01:00) Skopje</option>
                  <option value='Europe/Stockholm' {{ (DateHelper::getTimezone() == 'Europe/Stockholm')?'selected':'' }}>(UTC+01:00) Stockholm</option>
                  <option value='Europe/Vienna' {{ (DateHelper::getTimezone() == 'Europe/Vienna')?'selected':'' }}>(UTC+01:00) Vienna</option>
                  <option value='Europe/Warsaw' {{ (DateHelper::getTimezone() == 'Europe/Warsaw')?'selected':'' }}>(UTC+01:00) Warsaw</option>
                  <option value='Africa/Lagos' {{ (DateHelper::getTimezone() == 'Africa/Lagos')?'selected':'' }}>(UTC+01:00) West Central Africa</option>
                  <option value='Europe/Zagreb' {{ (DateHelper::getTimezone() == 'Europe/Zagreb')?'selected':'' }}>(UTC+01:00) Zagreb</option>
                  <option value='Europe/Zurich' {{ (DateHelper::getTimezone() == 'Europe/Zurich')?'selected':'' }}>(UTC+01:00) Zurich</option>
                  <option value='Europe/Athens' {{ (DateHelper::getTimezone() == 'Europe/Athens')?'selected':'' }}>(UTC+02:00) Athens</option>
                  <option value='Europe/Bucharest' {{ (DateHelper::getTimezone() == 'Europe/Bucharest')?'selected':'' }}>(UTC+02:00) Bucharest</option>
                  <option value='Africa/Cairo' {{ (DateHelper::getTimezone() == 'Africa/Cairo')?'selected':'' }}>(UTC+02:00) Cairo</option>
                  <option value='Africa/Harare' {{ (DateHelper::getTimezone() == 'Africa/Harare')?'selected':'' }}>(UTC+02:00) Harare</option>
                  <option value='Europe/Helsinki' {{ (DateHelper::getTimezone() == 'Europe/Helsinki')?'selected':'' }}>(UTC+02:00) Helsinki</option>
                  <option value='Europe/Istanbul' {{ (DateHelper::getTimezone() == 'Europe/Istanbul')?'selected':'' }}>(UTC+02:00) Istanbul</option>
                  <option value='Asia/Jerusalem' {{ (DateHelper::getTimezone() == 'Asia/Jerusalem')?'selected':'' }}>(UTC+02:00) Jerusalem</option>
                  <option value='Europe/Helsinki' {{ (DateHelper::getTimezone() == 'Europe/Helsinki')?'selected':'' }}>(UTC+02:00) Kyiv</option>
                  <option value='Africa/Johannesburg' {{ (DateHelper::getTimezone() == 'Africa/Johannesburg')?'selected':'' }}>(UTC+02:00) Pretoria</option>
                  <option value='Europe/Riga' {{ (DateHelper::getTimezone() == 'Europe/Riga')?'selected':'' }}>(UTC+02:00) Riga</option>
                  <option value='Europe/Sofia' {{ (DateHelper::getTimezone() == 'Europe/Sofia')?'selected':'' }}>(UTC+02:00) Sofia</option>
                  <option value='Europe/Tallinn' {{ (DateHelper::getTimezone() == 'Europe/Tallinn')?'selected':'' }}>(UTC+02:00) Tallinn</option>
                  <option value='Europe/Vilnius' {{ (DateHelper::getTimezone() == 'Europe/Vilnius')?'selected':'' }}>(UTC+02:00) Vilnius</option>
                  <option value='Asia/Baghdad' {{ (DateHelper::getTimezone() == 'Asia/Baghdad')?'selected':'' }}>(UTC+03:00) Baghdad</option>
                  <option value='Asia/Kuwait' {{ (DateHelper::getTimezone() == 'Asia/Kuwait')?'selected':'' }}>(UTC+03:00) Kuwait</option>
                  <option value='Europe/Minsk' {{ (DateHelper::getTimezone() == 'Europe/Minsk')?'selected':'' }}>(UTC+03:00) Minsk</option>
                  <option value='Africa/Nairobi' {{ (DateHelper::getTimezone() == 'Africa/Nairobi')?'selected':'' }}>(UTC+03:00) Nairobi</option>
                  <option value='Asia/Riyadh' {{ (DateHelper::getTimezone() == 'Asia/Riyadh')?'selected':'' }}>(UTC+03:00) Riyadh</option>
                  <option value='Europe/Volgograd' {{ (DateHelper::getTimezone() == 'Europe/Volgograd')?'selected':'' }}>(UTC+03:00) Volgograd</option>
                  <option value='Asia/Tehran' {{ (DateHelper::getTimezone() == 'Asia/Tehran')?'selected':'' }}>(UTC+03:30) Tehran</option>
                  <option value='Asia/Muscat' {{ (DateHelper::getTimezone() == 'Asia/Muscat')?'selected':'' }}>(UTC+04:00) Abu Dhabi</option>
                  <option value='Asia/Baku' {{ (DateHelper::getTimezone() == 'Asia/Baku')?'selected':'' }}>(UTC+04:00) Baku</option>
                  <option value='Europe/Moscow' {{ (DateHelper::getTimezone() == 'Europe/Moscow')?'selected':'' }}>(UTC+04:00) Moscow</option>
                  <option value='Asia/Muscat' {{ (DateHelper::getTimezone() == 'Asia/Muscat')?'selected':'' }}>(UTC+04:00) Muscat</option>
                  <option value='Europe/Moscow' {{ (DateHelper::getTimezone() == 'Europe/Moscow')?'selected':'' }}>(UTC+04:00) St. Petersburg</option>
                  <option value='Asia/Tbilisi' {{ (DateHelper::getTimezone() == 'Asia/Tbilisi')?'selected':'' }}>(UTC+04:00) Tbilisi</option>
                  <option value='Asia/Yerevan' {{ (DateHelper::getTimezone() == 'Asia/Yerevan')?'selected':'' }}>(UTC+04:00) Yerevan</option>
                  <option value='Asia/Kabul' {{ (DateHelper::getTimezone() == 'Asia/Kabul')?'selected':'' }}>(UTC+04:30) Kabul</option>
                  <option value='Asia/Karachi' {{ (DateHelper::getTimezone() == 'Asia/Karachi')?'selected':'' }}>(UTC+05:00) Islamabad</option>
                  <option value='Asia/Karachi' {{ (DateHelper::getTimezone() == 'Asia/Karachi')?'selected':'' }}>(UTC+05:00) Karachi</option>
                  <option value='Asia/Tashkent' {{ (DateHelper::getTimezone() == 'Asia/Tashkent')?'selected':'' }}>(UTC+05:00) Tashkent</option>
                  <option value='Asia/Calcutta' {{ (DateHelper::getTimezone() == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Chennai</option>
                  <option value='Asia/Kolkata' {{ (DateHelper::getTimezone() == 'Asia/Kolkata')?'selected':'' }}>(UTC+05:30) Kolkata</option>
                  <option value='Asia/Calcutta' {{ (DateHelper::getTimezone() == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Mumbai</option>
                  <option value='Asia/Calcutta' {{ (DateHelper::getTimezone() == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) New Delhi</option>
                  <option value='Asia/Calcutta' {{ (DateHelper::getTimezone() == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Sri Jayawardenepura</option>
                  <option value='Asia/Katmandu' {{ (DateHelper::getTimezone() == 'Asia/Katmandu')?'selected':'' }}>(UTC+05:45) Kathmandu</option>
                  <option value='Asia/Almaty' {{ (DateHelper::getTimezone() == 'Asia/Almaty')?'selected':'' }}>(UTC+06:00) Almaty</option>
                  <option value='Asia/Dhaka' {{ (DateHelper::getTimezone() == 'Asia/Dhaka')?'selected':'' }}>(UTC+06:00) Astana</option>
                  <option value='Asia/Dhaka' {{ (DateHelper::getTimezone() == 'Asia/Dhaka')?'selected':'' }}>(UTC+06:00) Dhaka</option>
                  <option value='Asia/Yekaterinburg' {{ (DateHelper::getTimezone() == 'Asia/Yekaterinburg')?'selected':'' }}>(UTC+06:00) Ekaterinburg</option>
                  <option value='Asia/Rangoon' {{ (DateHelper::getTimezone() == 'Asia/Rangoon')?'selected':'' }}>(UTC+06:30) Rangoon</option>
                  <option value='Asia/Bangkok' {{ (DateHelper::getTimezone() == 'Asia/Bangkok')?'selected':'' }}>(UTC+07:00) Bangkok</option>
                  <option value='Asia/Bangkok' {{ (DateHelper::getTimezone() == 'Asia/Bangkok')?'selected':'' }}>(UTC+07:00) Hanoi</option>
                  <option value='Asia/Jakarta' {{ (DateHelper::getTimezone() == 'Asia/Jakarta')?'selected':'' }}>(UTC+07:00) Jakarta</option>
                  <option value='Asia/Novosibirsk' {{ (DateHelper::getTimezone() == 'Asia/Novosibirsk')?'selected':'' }}>(UTC+07:00) Novosibirsk</option>
                  <option value='Asia/Hong_Kong' {{ (DateHelper::getTimezone() == 'Asia/Hong_Kong')?'selected':'' }}>(UTC+08:00) Beijing</option>
                  <option value='Asia/Chongqing' {{ (DateHelper::getTimezone() == 'Asia/Chongqing')?'selected':'' }}>(UTC+08:00) Chongqing</option>
                  <option value='Asia/Hong_Kong' {{ (DateHelper::getTimezone() == 'Asia/Hong_Kong')?'selected':'' }}>(UTC+08:00) Hong Kong</option>
                  <option value='Asia/Krasnoyarsk' {{ (DateHelper::getTimezone() == 'Asia/Krasnoyarsk')?'selected':'' }}>(UTC+08:00) Krasnoyarsk</option>
                  <option value='Asia/Kuala_Lumpur' {{ (DateHelper::getTimezone() == 'Asia/Kuala_Lumpur')?'selected':'' }}>(UTC+08:00) Kuala Lumpur</option>
                  <option value='Australia/Perth' {{ (DateHelper::getTimezone() == 'Australia/Perth')?'selected':'' }}>(UTC+08:00) Perth</option>
                  <option value='Asia/Singapore' {{ (DateHelper::getTimezone() == 'Asia/Singapore')?'selected':'' }}>(UTC+08:00) Singapore</option>
                  <option value='Asia/Taipei' {{ (DateHelper::getTimezone() == 'Asia/Taipei')?'selected':'' }}>(UTC+08:00) Taipei</option>
                  <option value='Asia/Ulan_Bator' {{ (DateHelper::getTimezone() == 'Asia/Ulan_Bator')?'selected':'' }}>(UTC+08:00) Ulaan Bataar</option>
                  <option value='Asia/Urumqi' {{ (DateHelper::getTimezone() == 'Asia/Urumqi')?'selected':'' }}>(UTC+08:00) Urumqi</option>
                  <option value='Asia/Irkutsk' {{ (DateHelper::getTimezone() == 'Asia/Irkutsk')?'selected':'' }}>(UTC+09:00) Irkutsk</option>
                  <option value='Asia/Tokyo' {{ (DateHelper::getTimezone() == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Osaka</option>
                  <option value='Asia/Tokyo' {{ (DateHelper::getTimezone() == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Sapporo</option>
                  <option value='Asia/Seoul' {{ (DateHelper::getTimezone() == 'Asia/Seoul')?'selected':'' }}>(UTC+09:00) Seoul</option>
                  <option value='Asia/Tokyo' {{ (DateHelper::getTimezone() == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Tokyo</option>
                  <option value='Australia/Adelaide' {{ (DateHelper::getTimezone() == 'Australia/Adelaide')?'selected':'' }}>(UTC+09:30) Adelaide</option>
                  <option value='Australia/Darwin' {{ (DateHelper::getTimezone() == 'Australia/Darwin')?'selected':'' }}>(UTC+09:30) Darwin</option>
                  <option value='Australia/Brisbane' {{ (DateHelper::getTimezone() == 'Australia/Brisbane')?'selected':'' }}>(UTC+10:00) Brisbane</option>
                  <option value='Australia/Canberra' {{ (DateHelper::getTimezone() == 'Australia/Canberra')?'selected':'' }}>(UTC+10:00) Canberra</option>
                  <option value='Pacific/Guam' {{ (DateHelper::getTimezone() == 'Pacific/Guam')?'selected':'' }}>(UTC+10:00) Guam</option>
                  <option value='Australia/Hobart' {{ (DateHelper::getTimezone() == 'Australia/Hobart')?'selected':'' }}>(UTC+10:00) Hobart</option>
                  <option value='Australia/Melbourne' {{ (DateHelper::getTimezone() == 'Australia/Melbourne')?'selected':'' }}>(UTC+10:00) Melbourne</option>
                  <option value='Pacific/Port_Moresby' {{ (DateHelper::getTimezone() == 'Pacific/Port_Moresby')?'selected':'' }}>(UTC+10:00) Port Moresby</option>
                  <option value='Australia/Sydney' {{ (DateHelper::getTimezone() == 'Australia/Sydney')?'selected':'' }}>(UTC+10:00) Sydney</option>
                  <option value='Asia/Yakutsk' {{ (DateHelper::getTimezone() == 'Asia/Yakutsk')?'selected':'' }}>(UTC+10:00) Yakutsk</option>
                  <option value='Asia/Vladivostok' {{ (DateHelper::getTimezone() == 'Asia/Vladivostok')?'selected':'' }}>(UTC+11:00) Vladivostok</option>
                  <option value='Pacific/Auckland' {{ (DateHelper::getTimezone() == 'Pacific/Auckland')?'selected':'' }}>(UTC+12:00) Auckland</option>
                  <option value='Pacific/Fiji' {{ (DateHelper::getTimezone() == 'Pacific/Fiji')?'selected':'' }}>(UTC+12:00) Fiji</option>
                  <option value='Pacific/Kwajalein' {{ (DateHelper::getTimezone() == 'Pacific/Kwajalein')?'selected':'' }}>(UTC+12:00) International Date Line West</option>
                  <option value='Asia/Kamchatka' {{ (DateHelper::getTimezone() == 'Asia/Kamchatka')?'selected':'' }}>(UTC+12:00) Kamchatka</option>
                  <option value='Asia/Magadan' {{ (DateHelper::getTimezone() == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) Magadan</option>
                  <option value='Pacific/Fiji' {{ (DateHelper::getTimezone() == 'Pacific/Fiji')?'selected':'' }}>(UTC+12:00) Marshall Is.</option>
                  <option value='Asia/Magadan' {{ (DateHelper::getTimezone() == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) New Caledonia</option>
                  <option value='Asia/Magadan' {{ (DateHelper::getTimezone() == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) Solomon Is.</option>
                  <option value='Pacific/Auckland' {{ (DateHelper::getTimezone() == 'Pacific/Auckland')?'selected':'' }}>(UTC+12:00) Wellington</option>
                  <option value='Pacific/Tongatapu' {{ (DateHelper::getTimezone() == 'Pacific/Tongatapu')?'selected':'' }}>(UTC+13:00) Nuku'alofa</option>

                </select>
              </div>

              {{-- Layout --}}
              <div class="form-group">
                <label for="layout">{{ trans('settings.layout') }}</label>
                <select class="form-control" name="layout" id="layout">
                  <option value='false' {{ (auth()->user()->fluid_container == 'false')?'selected':'' }}>{{ trans('settings.layout_small') }}</option>
                  <option value='true' {{ (auth()->user()->fluid_container == 'true')?'selected':'' }}>{{ trans('settings.layout_big') }}</option>
                </select>
              </div>

              {{-- Layout --}}
              <div class="form-group">
                <form-select
                  :value="'{{ auth()->user()->account->default_time_reminder_is_sent }}'"
                  :options="{{ $hours }}"
                  v-bind:id="'reminder_time'"
                  v-bind:title="'{{ trans('settings.reminder_time_to_send') }}'">
                </form-select>
              </div>

              <button type="submit" class="btn btn-primary">{{ trans('settings.save') }}</button>
            </form>
          </div>
        </div>

        <form method="POST" action="{{ route('settings.reset') }}" class="settings-reset bg-white" onsubmit="return confirm('{{ trans('settings.reset_notice') }}')">
          {{ csrf_field() }}

          <h2>{{ trans('settings.reset_title') }}</h2>
          <p>{{ trans('settings.reset_desc') }}</p>
          <button type="submit" class="btn">{{ trans('settings.reset_cta') }}</button>
        </form>

        <form method="POST" action="{{ route('settings.delete') }}" class="settings-delete bg-white" onsubmit="return confirm('{{ trans('settings.delete_notice') }}')">
          {{ csrf_field() }}

          <h2>{{ trans('settings.delete_title') }}</h2>
          <p>{{ trans('settings.delete_desc') }}</p>
          <button type="submit" class="btn">{{ trans('settings.delete_cta') }}</button>
        </form>

      </div>
    </div>
  </div>
</div>

@endsection
