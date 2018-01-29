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
              <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
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

            <form action="/settings/save" method="POST">
              {{ csrf_field() }}

              {{-- id --}}
              <input type="hidden" name="id" value="{{ auth()->user()->id }}" />

              {{-- names --}}
              <div class="form-group">
                <label for="firstname">First name</label>
                <input type="text" class="form-control" name="first_name" id="first_name" required value="{{ auth()->user()->first_name }}">
              </div>

              <div class="form-group">
                <label for="firstname">Last name</label>
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
                  @foreach(config('monica.langs') as $lang)
                    <option value="{{ $lang }}" {{ (auth()->user()->locale == $lang)?'selected':'' }}>{{ trans('settings.locale_'.$lang) }}</option>
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
                  <option value="firstname_first" {{ (auth()->user()->name_order == 'firstname_first')?'selected':'' }}>{{ trans('settings.name_order_firstname_first') }}</option>
                  <option value="lastname_first" {{ (auth()->user()->name_order == 'lastname_first')?'selected':'' }}>{{ trans('settings.name_order_lastname_first') }}</option>
                </select>
              </div>

              {{-- Timezone --}}
              <div class="form-group">
                <label for="timezone">{{ trans('settings.timezone') }}</label>
                <select class="form-control" name="timezone" id="timezone">
                  <option value='US/Eastern' {{ (auth()->user()->timezone == 'US/Eastern')?'selected':'' }}>(UTC-05:00) Montreal/New-York</option>
                  <option value='US/Central' {{ (auth()->user()->timezone == 'US/Central')?'selected':'' }}>(UTC-06:00) Central Time (US &amp; Canada)</option>
                  <option value='America/Los_Angeles' {{ (auth()->user()->timezone == 'America/Los_Angeles')?'selected':'' }}>(UTC-08:00) Pacific Time (US &amp; Canada)</option>
                  <option value='Pacific/Midway' {{ (auth()->user()->timezone == 'Pacific/Midway')?'selected':'' }}>(UTC-11:00) Midway Island</option>
                  <option value='Pacific/Samoa' {{ (auth()->user()->timezone == 'Pacific/Samoa')?'selected':'' }}>(UTC-11:00) Samoa</option>
                  <option value='Pacific/Honolulu' {{ (auth()->user()->timezone == 'Pacific/Honolulu')?'selected':'' }}>(UTC-10:00) Hawaii</option>
                  <option value='US/Alaska' {{ (auth()->user()->timezone == 'US/Alaska')?'selected':'' }}>(UTC-09:00) Alaska</option>
                  <option value='America/Tijuana' {{ (auth()->user()->timezone == 'America/Tijuana')?'selected':'' }}>(UTC-08:00) Tijuana</option>
                  <option value='US/Arizona' {{ (auth()->user()->timezone == 'US/Arizona')?'selected':'' }}>(UTC-07:00) Arizona</option>
                  <option value='America/Chihuahua' {{ (auth()->user()->timezone == 'America/Chihuahua')?'selected':'' }}>(UTC-07:00) Chihuahua</option>
                  <option value='America/Chihuahua' {{ (auth()->user()->timezone == 'America/Chihuahua')?'selected':'' }}>(UTC-07:00) La Paz</option>
                  <option value='America/Mazatlan' {{ (auth()->user()->timezone == 'America/Mazatlan')?'selected':'' }}>(UTC-07:00) Mazatlan</option>
                  <option value='US/Mountain' {{ (auth()->user()->timezone == 'US/Mountain')?'selected':'' }}>(UTC-07:00) Mountain Time (US &amp; Canada)</option>
                  <option value='America/Managua' {{ (auth()->user()->timezone == 'America/Managua')?'selected':'' }}>(UTC-06:00) Central America</option>
                  <option value='US/Central' {{ (auth()->user()->timezone == 'US/Central')?'selected':'' }}>(UTC-06:00) Central Time (US &amp; Canada)</option>
                  <option value='America/Mexico_City' {{ (auth()->user()->timezone == 'America/Mexico_City')?'selected':'' }}>(UTC-06:00) Guadalajara</option>
                  <option value='America/Mexico_City' {{ (auth()->user()->timezone == 'America/Mexico_City')?'selected':'' }}>(UTC-06:00) Mexico City</option>
                  <option value='America/Monterrey' {{ (auth()->user()->timezone == 'America/Monterrey')?'selected':'' }}>(UTC-06:00) Monterrey</option>
                  <option value='Canada/Saskatchewan' {{ (auth()->user()->timezone == 'Canada/Saskatchewan')?'selected':'' }}>(UTC-06:00) Saskatchewan</option>
                  <option value='America/Bogota' {{ (auth()->user()->timezone == 'America/Bogota')?'selected':'' }}>(UTC-05:00) Bogota</option>
                  <option value='US/Eastern' {{ (auth()->user()->timezone == 'US/Eastern')?'selected':'' }}>(UTC-05:00) Eastern Time (US &amp; Canada)</option>
                  <option value='US/East-Indiana' {{ (auth()->user()->timezone == 'US/East')?'selected':'' }}>(UTC-05:00) Indiana (East)</option>
                  <option value='America/Lima' {{ (auth()->user()->timezone == 'America/Lima')?'selected':'' }}>(UTC-05:00) Lima</option>
                  <option value='America/Bogota' {{ (auth()->user()->timezone == 'America/Bogota')?'selected':'' }}>(UTC-05:00) Quito</option>
                  <option value='Canada/Atlantic' {{ (auth()->user()->timezone == 'Canada/Atlantic')?'selected':'' }}>(UTC-04:00) Atlantic Time (Canada)</option>
                  <option value='America/Caracas' {{ (auth()->user()->timezone == 'America/Caracas')?'selected':'' }}>(UTC-04:30) Caracas</option>
                  <option value='America/La_Paz' {{ (auth()->user()->timezone == 'America/La_Paz')?'selected':'' }}>(UTC-04:00) La Paz</option>
                  <option value='America/Santiago' {{ (auth()->user()->timezone == 'America/Santiago')?'selected':'' }}>(UTC-04:00) Santiago</option>
                  <option value='Canada/Newfoundland' {{ (auth()->user()->timezone == 'Canada/Newfoundland')?'selected':'' }}>(UTC-03:30) Newfoundland</option>
                  <option value='America/Sao_Paulo' {{ (auth()->user()->timezone == 'America/Sao_Paulo')?'selected':'' }}>(UTC-03:00) Brasilia</option>
                  <option value='America/Argentina/Buenos_Aires' {{ (auth()->user()->timezone == 'America/Argentina')?'selected':'' }}>(UTC-03:00) Buenos Aires</option>
                  <option value='America/Godthab' {{ (auth()->user()->timezone == 'America/Godthab')?'selected':'' }}>(UTC-03:00) Greenland</option>
                  <option value='America/Noronha' {{ (auth()->user()->timezone == 'America/Noronha')?'selected':'' }}>(UTC-02:00) Mid-Atlantic</option>
                  <option value='Atlantic/Azores' {{ (auth()->user()->timezone == 'Atlantic/Azores')?'selected':'' }}>(UTC-01:00) Azores</option>
                  <option value='Atlantic/Cape_Verde' {{ (auth()->user()->timezone == 'Atlantic/Cape_Verde')?'selected':'' }}>(UTC-01:00) Cape Verde Is.</option>
                  <option value='Africa/Casablanca' {{ (auth()->user()->timezone == 'Africa/Casablanca')?'selected':'' }}>(UTC+00:00) Casablanca</option>
                  <option value='Europe/London' {{ (auth()->user()->timezone == 'Europe/London')?'selected':'' }}>(UTC+00:00) Edinburgh</option>
                  <option value='Etc/Greenwich' {{ (auth()->user()->timezone == 'Etc/Greenwich')?'selected':'' }}>(UTC+00:00) Greenwich Mean Time : Dublin</option>
                  <option value='Europe/Lisbon' {{ (auth()->user()->timezone == 'Europe/Lisbon')?'selected':'' }}>(UTC+00:00) Lisbon</option>
                  <option value='Europe/London' {{ (auth()->user()->timezone == 'Europe/London')?'selected':'' }}>(UTC+00:00) London</option>
                  <option value='Africa/Monrovia' {{ (auth()->user()->timezone == 'Africa/Monrovia')?'selected':'' }}>(UTC+00:00) Monrovia</option>
                  <option value='UTC' {{ (auth()->user()->timezone == 'UTC')?'selected':'' }}>(UTC+00:00) UTC</option>
                  <option value='Europe/Amsterdam' {{ (auth()->user()->timezone == 'Europe/Amsterdam')?'selected':'' }}>(UTC+01:00) Amsterdam</option>
                  <option value='Europe/Belgrade' {{ (auth()->user()->timezone == 'Europe/Belgrade')?'selected':'' }}>(UTC+01:00) Belgrade</option>
                  <option value='Europe/Berlin' {{ (auth()->user()->timezone == 'Europe/Berlin')?'selected':'' }}>(UTC+01:00) Berlin</option>
                  <option value='Europe/Bratislava' {{ (auth()->user()->timezone == 'Europe/Bratislava')?'selected':'' }}>(UTC+01:00) Bratislava</option>
                  <option value='Europe/Brussels' {{ (auth()->user()->timezone == 'Europe/Brussels')?'selected':'' }}>(UTC+01:00) Brussels</option>
                  <option value='Europe/Budapest' {{ (auth()->user()->timezone == 'Europe/Budapest')?'selected':'' }}>(UTC+01:00) Budapest</option>
                  <option value='Europe/Copenhagen' {{ (auth()->user()->timezone == 'Europe/Copenhagen')?'selected':'' }}>(UTC+01:00) Copenhagen</option>
                  <option value='Europe/Ljubljana' {{ (auth()->user()->timezone == 'Europe/Ljubljana')?'selected':'' }}>(UTC+01:00) Ljubljana</option>
                  <option value='Europe/Madrid' {{ (auth()->user()->timezone == 'Europe/Madrid')?'selected':'' }}>(UTC+01:00) Madrid</option>
                  <option value='Europe/Paris' {{ (auth()->user()->timezone == 'Europe/Paris')?'selected':'' }}>(UTC+01:00) Paris</option>
                  <option value='Europe/Prague' {{ (auth()->user()->timezone == 'Europe/Prague')?'selected':'' }}>(UTC+01:00) Prague</option>
                  <option value='Europe/Rome' {{ (auth()->user()->timezone == 'Europe/Rome')?'selected':'' }}>(UTC+01:00) Rome</option>
                  <option value='Europe/Sarajevo' {{ (auth()->user()->timezone == 'Europe/Sarajevo')?'selected':'' }}>(UTC+01:00) Sarajevo</option>
                  <option value='Europe/Skopje' {{ (auth()->user()->timezone == 'Europe/Skopje')?'selected':'' }}>(UTC+01:00) Skopje</option>
                  <option value='Europe/Stockholm' {{ (auth()->user()->timezone == 'Europe/Stockholm')?'selected':'' }}>(UTC+01:00) Stockholm</option>
                  <option value='Europe/Vienna' {{ (auth()->user()->timezone == 'Europe/Vienna')?'selected':'' }}>(UTC+01:00) Vienna</option>
                  <option value='Europe/Warsaw' {{ (auth()->user()->timezone == 'Europe/Warsaw')?'selected':'' }}>(UTC+01:00) Warsaw</option>
                  <option value='Africa/Lagos' {{ (auth()->user()->timezone == 'Africa/Lagos')?'selected':'' }}>(UTC+01:00) West Central Africa</option>
                  <option value='Europe/Zagreb' {{ (auth()->user()->timezone == 'Europe/Zagreb')?'selected':'' }}>(UTC+01:00) Zagreb</option>
                  <option value='Europe/Zurich' {{ (auth()->user()->timezone == 'Europe/Zurich')?'selected':'' }}>(UTC+01:00) Zurich</option>
                  <option value='Europe/Athens' {{ (auth()->user()->timezone == 'Europe/Athens')?'selected':'' }}>(UTC+02:00) Athens</option>
                  <option value='Europe/Bucharest' {{ (auth()->user()->timezone == 'Europe/Bucharest')?'selected':'' }}>(UTC+02:00) Bucharest</option>
                  <option value='Africa/Cairo' {{ (auth()->user()->timezone == 'Africa/Cairo')?'selected':'' }}>(UTC+02:00) Cairo</option>
                  <option value='Africa/Harare' {{ (auth()->user()->timezone == 'Africa/Harare')?'selected':'' }}>(UTC+02:00) Harare</option>
                  <option value='Europe/Helsinki' {{ (auth()->user()->timezone == 'Europe/Helsinki')?'selected':'' }}>(UTC+02:00) Helsinki</option>
                  <option value='Europe/Istanbul' {{ (auth()->user()->timezone == 'Europe/Istanbul')?'selected':'' }}>(UTC+02:00) Istanbul</option>
                  <option value='Asia/Jerusalem' {{ (auth()->user()->timezone == 'Asia/Jerusalem')?'selected':'' }}>(UTC+02:00) Jerusalem</option>
                  <option value='Europe/Helsinki' {{ (auth()->user()->timezone == 'Europe/Helsinki')?'selected':'' }}>(UTC+02:00) Kyiv</option>
                  <option value='Africa/Johannesburg' {{ (auth()->user()->timezone == 'Africa/Johannesburg')?'selected':'' }}>(UTC+02:00) Pretoria</option>
                  <option value='Europe/Riga' {{ (auth()->user()->timezone == 'Europe/Riga')?'selected':'' }}>(UTC+02:00) Riga</option>
                  <option value='Europe/Sofia' {{ (auth()->user()->timezone == 'Europe/Sofia')?'selected':'' }}>(UTC+02:00) Sofia</option>
                  <option value='Europe/Tallinn' {{ (auth()->user()->timezone == 'Europe/Tallinn')?'selected':'' }}>(UTC+02:00) Tallinn</option>
                  <option value='Europe/Vilnius' {{ (auth()->user()->timezone == 'Europe/Vilnius')?'selected':'' }}>(UTC+02:00) Vilnius</option>
                  <option value='Asia/Baghdad' {{ (auth()->user()->timezone == 'Asia/Baghdad')?'selected':'' }}>(UTC+03:00) Baghdad</option>
                  <option value='Asia/Kuwait' {{ (auth()->user()->timezone == 'Asia/Kuwait')?'selected':'' }}>(UTC+03:00) Kuwait</option>
                  <option value='Europe/Minsk' {{ (auth()->user()->timezone == 'Europe/Minsk')?'selected':'' }}>(UTC+03:00) Minsk</option>
                  <option value='Africa/Nairobi' {{ (auth()->user()->timezone == 'Africa/Nairobi')?'selected':'' }}>(UTC+03:00) Nairobi</option>
                  <option value='Asia/Riyadh' {{ (auth()->user()->timezone == 'Asia/Riyadh')?'selected':'' }}>(UTC+03:00) Riyadh</option>
                  <option value='Europe/Volgograd' {{ (auth()->user()->timezone == 'Europe/Volgograd')?'selected':'' }}>(UTC+03:00) Volgograd</option>
                  <option value='Asia/Tehran' {{ (auth()->user()->timezone == 'Asia/Tehran')?'selected':'' }}>(UTC+03:30) Tehran</option>
                  <option value='Asia/Muscat' {{ (auth()->user()->timezone == 'Asia/Muscat')?'selected':'' }}>(UTC+04:00) Abu Dhabi</option>
                  <option value='Asia/Baku' {{ (auth()->user()->timezone == 'Asia/Baku')?'selected':'' }}>(UTC+04:00) Baku</option>
                  <option value='Europe/Moscow' {{ (auth()->user()->timezone == 'Europe/Moscow')?'selected':'' }}>(UTC+04:00) Moscow</option>
                  <option value='Asia/Muscat' {{ (auth()->user()->timezone == 'Asia/Muscat')?'selected':'' }}>(UTC+04:00) Muscat</option>
                  <option value='Europe/Moscow' {{ (auth()->user()->timezone == 'Europe/Moscow')?'selected':'' }}>(UTC+04:00) St. Petersburg</option>
                  <option value='Asia/Tbilisi' {{ (auth()->user()->timezone == 'Asia/Tbilisi')?'selected':'' }}>(UTC+04:00) Tbilisi</option>
                  <option value='Asia/Yerevan' {{ (auth()->user()->timezone == 'Asia/Yerevan')?'selected':'' }}>(UTC+04:00) Yerevan</option>
                  <option value='Asia/Kabul' {{ (auth()->user()->timezone == 'Asia/Kabul')?'selected':'' }}>(UTC+04:30) Kabul</option>
                  <option value='Asia/Karachi' {{ (auth()->user()->timezone == 'Asia/Karachi')?'selected':'' }}>(UTC+05:00) Islamabad</option>
                  <option value='Asia/Karachi' {{ (auth()->user()->timezone == 'Asia/Karachi')?'selected':'' }}>(UTC+05:00) Karachi</option>
                  <option value='Asia/Tashkent' {{ (auth()->user()->timezone == 'Asia/Tashkent')?'selected':'' }}>(UTC+05:00) Tashkent</option>
                  <option value='Asia/Calcutta' {{ (auth()->user()->timezone == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Chennai</option>
                  <option value='Asia/Kolkata' {{ (auth()->user()->timezone == 'Asia/Kolkata')?'selected':'' }}>(UTC+05:30) Kolkata</option>
                  <option value='Asia/Calcutta' {{ (auth()->user()->timezone == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Mumbai</option>
                  <option value='Asia/Calcutta' {{ (auth()->user()->timezone == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) New Delhi</option>
                  <option value='Asia/Calcutta' {{ (auth()->user()->timezone == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Sri Jayawardenepura</option>
                  <option value='Asia/Katmandu' {{ (auth()->user()->timezone == 'Asia/Katmandu')?'selected':'' }}>(UTC+05:45) Kathmandu</option>
                  <option value='Asia/Almaty' {{ (auth()->user()->timezone == 'Asia/Almaty')?'selected':'' }}>(UTC+06:00) Almaty</option>
                  <option value='Asia/Dhaka' {{ (auth()->user()->timezone == 'Asia/Dhaka')?'selected':'' }}>(UTC+06:00) Astana</option>
                  <option value='Asia/Dhaka' {{ (auth()->user()->timezone == 'Asia/Dhaka')?'selected':'' }}>(UTC+06:00) Dhaka</option>
                  <option value='Asia/Yekaterinburg' {{ (auth()->user()->timezone == 'Asia/Yekaterinburg')?'selected':'' }}>(UTC+06:00) Ekaterinburg</option>
                  <option value='Asia/Rangoon' {{ (auth()->user()->timezone == 'Asia/Rangoon')?'selected':'' }}>(UTC+06:30) Rangoon</option>
                  <option value='Asia/Bangkok' {{ (auth()->user()->timezone == 'Asia/Bangkok')?'selected':'' }}>(UTC+07:00) Bangkok</option>
                  <option value='Asia/Bangkok' {{ (auth()->user()->timezone == 'Asia/Bangkok')?'selected':'' }}>(UTC+07:00) Hanoi</option>
                  <option value='Asia/Jakarta' {{ (auth()->user()->timezone == 'Asia/Jakarta')?'selected':'' }}>(UTC+07:00) Jakarta</option>
                  <option value='Asia/Novosibirsk' {{ (auth()->user()->timezone == 'Asia/Novosibirsk')?'selected':'' }}>(UTC+07:00) Novosibirsk</option>
                  <option value='Asia/Hong_Kong' {{ (auth()->user()->timezone == 'Asia/Hong_Kong')?'selected':'' }}>(UTC+08:00) Beijing</option>
                  <option value='Asia/Chongqing' {{ (auth()->user()->timezone == 'Asia/Chongqing')?'selected':'' }}>(UTC+08:00) Chongqing</option>
                  <option value='Asia/Hong_Kong' {{ (auth()->user()->timezone == 'Asia/Hong_Kong')?'selected':'' }}>(UTC+08:00) Hong Kong</option>
                  <option value='Asia/Krasnoyarsk' {{ (auth()->user()->timezone == 'Asia/Krasnoyarsk')?'selected':'' }}>(UTC+08:00) Krasnoyarsk</option>
                  <option value='Asia/Kuala_Lumpur' {{ (auth()->user()->timezone == 'Asia/Kuala_Lumpur')?'selected':'' }}>(UTC+08:00) Kuala Lumpur</option>
                  <option value='Australia/Perth' {{ (auth()->user()->timezone == 'Australia/Perth')?'selected':'' }}>(UTC+08:00) Perth</option>
                  <option value='Asia/Singapore' {{ (auth()->user()->timezone == 'Asia/Singapore')?'selected':'' }}>(UTC+08:00) Singapore</option>
                  <option value='Asia/Taipei' {{ (auth()->user()->timezone == 'Asia/Taipei')?'selected':'' }}>(UTC+08:00) Taipei</option>
                  <option value='Asia/Ulan_Bator' {{ (auth()->user()->timezone == 'Asia/Ulan_Bator')?'selected':'' }}>(UTC+08:00) Ulaan Bataar</option>
                  <option value='Asia/Urumqi' {{ (auth()->user()->timezone == 'Asia/Urumqi')?'selected':'' }}>(UTC+08:00) Urumqi</option>
                  <option value='Asia/Irkutsk' {{ (auth()->user()->timezone == 'Asia/Irkutsk')?'selected':'' }}>(UTC+09:00) Irkutsk</option>
                  <option value='Asia/Tokyo' {{ (auth()->user()->timezone == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Osaka</option>
                  <option value='Asia/Tokyo' {{ (auth()->user()->timezone == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Sapporo</option>
                  <option value='Asia/Seoul' {{ (auth()->user()->timezone == 'Asia/Seoul')?'selected':'' }}>(UTC+09:00) Seoul</option>
                  <option value='Asia/Tokyo' {{ (auth()->user()->timezone == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Tokyo</option>
                  <option value='Australia/Adelaide' {{ (auth()->user()->timezone == 'Australia/Adelaide')?'selected':'' }}>(UTC+09:30) Adelaide</option>
                  <option value='Australia/Darwin' {{ (auth()->user()->timezone == 'Australia/Darwin')?'selected':'' }}>(UTC+09:30) Darwin</option>
                  <option value='Australia/Brisbane' {{ (auth()->user()->timezone == 'Australia/Brisbane')?'selected':'' }}>(UTC+10:00) Brisbane</option>
                  <option value='Australia/Canberra' {{ (auth()->user()->timezone == 'Australia/Canberra')?'selected':'' }}>(UTC+10:00) Canberra</option>
                  <option value='Pacific/Guam' {{ (auth()->user()->timezone == 'Pacific/Guam')?'selected':'' }}>(UTC+10:00) Guam</option>
                  <option value='Australia/Hobart' {{ (auth()->user()->timezone == 'Australia/Hobart')?'selected':'' }}>(UTC+10:00) Hobart</option>
                  <option value='Australia/Melbourne' {{ (auth()->user()->timezone == 'Australia/Melbourne')?'selected':'' }}>(UTC+10:00) Melbourne</option>
                  <option value='Pacific/Port_Moresby' {{ (auth()->user()->timezone == 'Pacific/Port_Moresby')?'selected':'' }}>(UTC+10:00) Port Moresby</option>
                  <option value='Australia/Sydney' {{ (auth()->user()->timezone == 'Australia/Sydney')?'selected':'' }}>(UTC+10:00) Sydney</option>
                  <option value='Asia/Yakutsk' {{ (auth()->user()->timezone == 'Asia/Yakutsk')?'selected':'' }}>(UTC+10:00) Yakutsk</option>
                  <option value='Asia/Vladivostok' {{ (auth()->user()->timezone == 'Asia/Vladivostok')?'selected':'' }}>(UTC+11:00) Vladivostok</option>
                  <option value='Pacific/Auckland' {{ (auth()->user()->timezone == 'Pacific/Auckland')?'selected':'' }}>(UTC+12:00) Auckland</option>
                  <option value='Pacific/Fiji' {{ (auth()->user()->timezone == 'Pacific/Fiji')?'selected':'' }}>(UTC+12:00) Fiji</option>
                  <option value='Pacific/Kwajalein' {{ (auth()->user()->timezone == 'Pacific/Kwajalein')?'selected':'' }}>(UTC+12:00) International Date Line West</option>
                  <option value='Asia/Kamchatka' {{ (auth()->user()->timezone == 'Asia/Kamchatka')?'selected':'' }}>(UTC+12:00) Kamchatka</option>
                  <option value='Asia/Magadan' {{ (auth()->user()->timezone == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) Magadan</option>
                  <option value='Pacific/Fiji' {{ (auth()->user()->timezone == 'Pacific/Fiji')?'selected':'' }}>(UTC+12:00) Marshall Is.</option>
                  <option value='Asia/Magadan' {{ (auth()->user()->timezone == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) New Caledonia</option>
                  <option value='Asia/Magadan' {{ (auth()->user()->timezone == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) Solomon Is.</option>
                  <option value='Pacific/Auckland' {{ (auth()->user()->timezone == 'Pacific/Auckland')?'selected':'' }}>(UTC+12:00) Wellington</option>
                  <option value='Pacific/Tongatapu' {{ (auth()->user()->timezone == 'Pacific/Tongatapu')?'selected':'' }}>(UTC+13:00) Nuku'alofa</option>

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

              <button type="submit" class="btn btn-primary">{{ trans('settings.save') }}</button>
            </form>
          </div>
        </div>

        <form method="POST" action="{{ action('SettingsController@reset') }}" class="settings-reset bg-white" onsubmit="return confirm('{{ trans('settings.reset_notice') }}')">
          {{ csrf_field() }}

          <h2>{{ trans('settings.reset_title') }}</h2>
          <p>{{ trans('settings.reset_desc') }}</p>
          <button type="submit" class="btn">{{ trans('settings.reset_cta') }}</button>
        </form>

        <form method="POST" action="{{ action('SettingsController@delete') }}" class="settings-delete bg-white" onsubmit="return confirm('{{ trans('settings.delete_notice') }}')">
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
