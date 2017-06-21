@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ Auth::user()->getFluidLayout() }}">
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

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-md-9">

        @include('partials.errors')

        @if (session('status'))
          <div class="alert alert-success">
              {{ session('status') }}
          </div>
        @endif

        <form action="/settings/save" method="POST">
          {{ csrf_field() }}
          <div class="form-group">
            <p>{{ trans('settings.name', ['firstname' => Auth::user()->first_name, 'lastname' => Auth::user()->last_name]) }}</p>
          </div>
          <div class="form-group">
            <label for="locale">{{ trans('settings.locale') }}</label>
            <select class="form-control" name="locale" id="locale">
              @foreach(config('monica.langs') as $lang)
                <option value="{{ $lang }}" {{ (Auth::user()->locale == $lang)?'selected':'' }}>{{ trans('settings.locale_'.$lang) }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label for="email">{{ trans('settings.email') }}</label>
            <input type="email" class="form-control" name="email" id="email" placeholder="{{ trans('settings.email_placeholder') }}" required value="{{ Auth::user()->email }}">
            <small id="emailHelp" class="form-text text-muted">{{ trans('settings.email_help') }}</small>
          </div>
          <div class="form-group">
            <label for="timezone">{{ trans('settings.timezone') }}</label>
            <select class="form-control" name="timezone" id="timezone">
              <option value='US/Eastern' {{ (Auth::user()->timezone == 'US/Eastern')?'selected':'' }}>(UTC-05:00) Montreal/New-York</option>
              <option value='US/Central' {{ (Auth::user()->timezone == 'US/Central')?'selected':'' }}>(UTC-06:00) Central Time (US &amp; Canada)</option>
              <option value='America/Los_Angeles' {{ (Auth::user()->timezone == 'America/Los_Angeles')?'selected':'' }}>(UTC-08:00) Pacific Time (US &amp; Canada)</option>
              <option value='Pacific/Midway' {{ (Auth::user()->timezone == 'Pacific/Midway')?'selected':'' }}>(UTC-11:00) Midway Island</option>
              <option value='Pacific/Samoa' {{ (Auth::user()->timezone == 'Pacific/Samoa')?'selected':'' }}>(UTC-11:00) Samoa</option>
              <option value='Pacific/Honolulu' {{ (Auth::user()->timezone == 'Pacific/Honolulu')?'selected':'' }}>(UTC-10:00) Hawaii</option>
              <option value='US/Alaska' {{ (Auth::user()->timezone == 'US/Alaska')?'selected':'' }}>(UTC-09:00) Alaska</option>
              <option value='America/Tijuana' {{ (Auth::user()->timezone == 'America/Tijuana')?'selected':'' }}>(UTC-08:00) Tijuana</option>
              <option value='US/Arizona' {{ (Auth::user()->timezone == 'US/Arizona')?'selected':'' }}>(UTC-07:00) Arizona</option>
              <option value='America/Chihuahua' {{ (Auth::user()->timezone == 'America/Chihuahua')?'selected':'' }}>(UTC-07:00) Chihuahua</option>
              <option value='America/Chihuahua' {{ (Auth::user()->timezone == 'America/Chihuahua')?'selected':'' }}>(UTC-07:00) La Paz</option>
              <option value='America/Mazatlan' {{ (Auth::user()->timezone == 'America/Mazatlan')?'selected':'' }}>(UTC-07:00) Mazatlan</option>
              <option value='US/Mountain' {{ (Auth::user()->timezone == 'US/Mountain')?'selected':'' }}>(UTC-07:00) Mountain Time (US &amp; Canada)</option>
              <option value='America/Managua' {{ (Auth::user()->timezone == 'America/Managua')?'selected':'' }}>(UTC-06:00) Central America</option>
              <option value='US/Central' {{ (Auth::user()->timezone == 'US/Central')?'selected':'' }}>(UTC-06:00) Central Time (US &amp; Canada)</option>
              <option value='America/Mexico_City' {{ (Auth::user()->timezone == 'America/Mexico_City')?'selected':'' }}>(UTC-06:00) Guadalajara</option>
              <option value='America/Mexico_City' {{ (Auth::user()->timezone == 'America/Mexico_City')?'selected':'' }}>(UTC-06:00) Mexico City</option>
              <option value='America/Monterrey' {{ (Auth::user()->timezone == 'America/Monterrey')?'selected':'' }}>(UTC-06:00) Monterrey</option>
              <option value='Canada/Saskatchewan' {{ (Auth::user()->timezone == 'Canada/Saskatchewan')?'selected':'' }}>(UTC-06:00) Saskatchewan</option>
              <option value='America/Bogota' {{ (Auth::user()->timezone == 'America/Bogota')?'selected':'' }}>(UTC-05:00) Bogota</option>
              <option value='US/Eastern' {{ (Auth::user()->timezone == 'US/Eastern')?'selected':'' }}>(UTC-05:00) Eastern Time (US &amp; Canada)</option>
              <option value='US/East- {{ (Auth::user()->timezone == 'US/East')?'selected':'' }}Indiana'>(UTC-05:00) Indiana (East)</option>
              <option value='America/Lima' {{ (Auth::user()->timezone == 'America/Lima')?'selected':'' }}>(UTC-05:00) Lima</option>
              <option value='America/Bogota' {{ (Auth::user()->timezone == 'America/Bogota')?'selected':'' }}>(UTC-05:00) Quito</option>
              <option value='Canada/Atlantic' {{ (Auth::user()->timezone == 'Canada/Atlantic')?'selected':'' }}>(UTC-04:00) Atlantic Time (Canada)</option>
              <option value='America/Caracas' {{ (Auth::user()->timezone == 'America/Caracas')?'selected':'' }}>(UTC-04:30) Caracas</option>
              <option value='America/La_Paz' {{ (Auth::user()->timezone == 'America/La_Paz')?'selected':'' }}>(UTC-04:00) La Paz</option>
              <option value='America/Santiago' {{ (Auth::user()->timezone == 'America/Santiago')?'selected':'' }}>(UTC-04:00) Santiago</option>
              <option value='Canada/Newfoundland' {{ (Auth::user()->timezone == 'Canada/Newfoundland')?'selected':'' }}>(UTC-03:30) Newfoundland</option>
              <option value='America/Sao_Paulo' {{ (Auth::user()->timezone == 'America/Sao_Paulo')?'selected':'' }}>(UTC-03:00) Brasilia</option>
              <option value='America/Argentina/Buenos_Aires' {{ (Auth::user()->timezone == 'America/Argentina')?'selected':'' }}>(UTC-03:00) Buenos Aires</option>
              <option value='America/Godthab' {{ (Auth::user()->timezone == 'America/Godthab')?'selected':'' }}>(UTC-03:00) Greenland</option>
              <option value='America/Noronha' {{ (Auth::user()->timezone == 'America/Noronha')?'selected':'' }}>(UTC-02:00) Mid-Atlantic</option>
              <option value='Atlantic/Azores' {{ (Auth::user()->timezone == 'Atlantic/Azores')?'selected':'' }}>(UTC-01:00) Azores</option>
              <option value='Atlantic/Cape_Verde' {{ (Auth::user()->timezone == 'Atlantic/Cape_Verde')?'selected':'' }}>(UTC-01:00) Cape Verde Is.</option>
              <option value='Africa/Casablanca' {{ (Auth::user()->timezone == 'Africa/Casablanca')?'selected':'' }}>(UTC+00:00) Casablanca</option>
              <option value='Europe/London' {{ (Auth::user()->timezone == 'Europe/London')?'selected':'' }}>(UTC+00:00) Edinburgh</option>
              <option value='Etc/Greenwich' {{ (Auth::user()->timezone == 'Etc/Greenwich')?'selected':'' }}>(UTC+00:00) Greenwich Mean Time : Dublin</option>
              <option value='Europe/Lisbon' {{ (Auth::user()->timezone == 'Europe/Lisbon')?'selected':'' }}>(UTC+00:00) Lisbon</option>
              <option value='Europe/London' {{ (Auth::user()->timezone == 'Europe/London')?'selected':'' }}>(UTC+00:00) London</option>
              <option value='Africa/Monrovia' {{ (Auth::user()->timezone == 'Africa/Monrovia')?'selected':'' }}>(UTC+00:00) Monrovia</option>
              <option value='UTC' {{ (Auth::user()->timezone == 'UTC')?'selected':'' }}>(UTC+00:00) UTC</option>
              <option value='Europe/Amsterdam' {{ (Auth::user()->timezone == 'Europe/Amsterdam')?'selected':'' }}>(UTC+01:00) Amsterdam</option>
              <option value='Europe/Belgrade' {{ (Auth::user()->timezone == 'Europe/Belgrade')?'selected':'' }}>(UTC+01:00) Belgrade</option>
              <option value='Europe/Berlin' {{ (Auth::user()->timezone == 'Europe/Berlin')?'selected':'' }}>(UTC+01:00) Berlin</option>
              <option value='Europe/Bratislava' {{ (Auth::user()->timezone == 'Europe/Bratislava')?'selected':'' }}>(UTC+01:00) Bratislava</option>
              <option value='Europe/Brussels' {{ (Auth::user()->timezone == 'Europe/Brussels')?'selected':'' }}>(UTC+01:00) Brussels</option>
              <option value='Europe/Budapest' {{ (Auth::user()->timezone == 'Europe/Budapest')?'selected':'' }}>(UTC+01:00) Budapest</option>
              <option value='Europe/Copenhagen' {{ (Auth::user()->timezone == 'Europe/Copenhagen')?'selected':'' }}>(UTC+01:00) Copenhagen</option>
              <option value='Europe/Ljubljana' {{ (Auth::user()->timezone == 'Europe/Ljubljana')?'selected':'' }}>(UTC+01:00) Ljubljana</option>
              <option value='Europe/Madrid' {{ (Auth::user()->timezone == 'Europe/Madrid')?'selected':'' }}>(UTC+01:00) Madrid</option>
              <option value='Europe/Paris' {{ (Auth::user()->timezone == 'Europe/Paris')?'selected':'' }}>(UTC+01:00) Paris</option>
              <option value='Europe/Prague' {{ (Auth::user()->timezone == 'Europe/Prague')?'selected':'' }}>(UTC+01:00) Prague</option>
              <option value='Europe/Rome' {{ (Auth::user()->timezone == 'Europe/Rome')?'selected':'' }}>(UTC+01:00) Rome</option>
              <option value='Europe/Sarajevo' {{ (Auth::user()->timezone == 'Europe/Sarajevo')?'selected':'' }}>(UTC+01:00) Sarajevo</option>
              <option value='Europe/Skopje' {{ (Auth::user()->timezone == 'Europe/Skopje')?'selected':'' }}>(UTC+01:00) Skopje</option>
              <option value='Europe/Stockholm' {{ (Auth::user()->timezone == 'Europe/Stockholm')?'selected':'' }}>(UTC+01:00) Stockholm</option>
              <option value='Europe/Vienna' {{ (Auth::user()->timezone == 'Europe/Vienna')?'selected':'' }}>(UTC+01:00) Vienna</option>
              <option value='Europe/Warsaw' {{ (Auth::user()->timezone == 'Europe/Warsaw')?'selected':'' }}>(UTC+01:00) Warsaw</option>
              <option value='Africa/Lagos' {{ (Auth::user()->timezone == 'Africa/Lagos')?'selected':'' }}>(UTC+01:00) West Central Africa</option>
              <option value='Europe/Zagreb' {{ (Auth::user()->timezone == 'Europe/Zagreb')?'selected':'' }}>(UTC+01:00) Zagreb</option>
              <option value='Europe/Zurich' {{ (Auth::user()->timezone == 'Europe/Zurich')?'selected':'' }}>(UTC+01:00) Zurich</option>
              <option value='Europe/Athens' {{ (Auth::user()->timezone == 'Europe/Athens')?'selected':'' }}>(UTC+02:00) Athens</option>
              <option value='Europe/Bucharest' {{ (Auth::user()->timezone == 'Europe/Bucharest')?'selected':'' }}>(UTC+02:00) Bucharest</option>
              <option value='Africa/Cairo' {{ (Auth::user()->timezone == 'Africa/Cairo')?'selected':'' }}>(UTC+02:00) Cairo</option>
              <option value='Africa/Harare' {{ (Auth::user()->timezone == 'Africa/Harare')?'selected':'' }}>(UTC+02:00) Harare</option>
              <option value='Europe/Helsinki' {{ (Auth::user()->timezone == 'Europe/Helsinki')?'selected':'' }}>(UTC+02:00) Helsinki</option>
              <option value='Europe/Istanbul' {{ (Auth::user()->timezone == 'Europe/Istanbul')?'selected':'' }}>(UTC+02:00) Istanbul</option>
              <option value='Asia/Jerusalem' {{ (Auth::user()->timezone == 'Asia/Jerusalem')?'selected':'' }}>(UTC+02:00) Jerusalem</option>
              <option value='Europe/Helsinki' {{ (Auth::user()->timezone == 'Europe/Helsinki')?'selected':'' }}>(UTC+02:00) Kyiv</option>
              <option value='Africa/Johannesburg' {{ (Auth::user()->timezone == 'Africa/Johannesburg')?'selected':'' }}>(UTC+02:00) Pretoria</option>
              <option value='Europe/Riga' {{ (Auth::user()->timezone == 'Europe/Riga')?'selected':'' }}>(UTC+02:00) Riga</option>
              <option value='Europe/Sofia' {{ (Auth::user()->timezone == 'Europe/Sofia')?'selected':'' }}>(UTC+02:00) Sofia</option>
              <option value='Europe/Tallinn' {{ (Auth::user()->timezone == 'Europe/Tallinn')?'selected':'' }}>(UTC+02:00) Tallinn</option>
              <option value='Europe/Vilnius' {{ (Auth::user()->timezone == 'Europe/Vilnius')?'selected':'' }}>(UTC+02:00) Vilnius</option>
              <option value='Asia/Baghdad' {{ (Auth::user()->timezone == 'Asia/Baghdad')?'selected':'' }}>(UTC+03:00) Baghdad</option>
              <option value='Asia/Kuwait' {{ (Auth::user()->timezone == 'Asia/Kuwait')?'selected':'' }}>(UTC+03:00) Kuwait</option>
              <option value='Europe/Minsk' {{ (Auth::user()->timezone == 'Europe/Minsk')?'selected':'' }}>(UTC+03:00) Minsk</option>
              <option value='Africa/Nairobi' {{ (Auth::user()->timezone == 'Africa/Nairobi')?'selected':'' }}>(UTC+03:00) Nairobi</option>
              <option value='Asia/Riyadh' {{ (Auth::user()->timezone == 'Asia/Riyadh')?'selected':'' }}>(UTC+03:00) Riyadh</option>
              <option value='Europe/Volgograd' {{ (Auth::user()->timezone == 'Europe/Volgograd')?'selected':'' }}>(UTC+03:00) Volgograd</option>
              <option value='Asia/Tehran' {{ (Auth::user()->timezone == 'Asia/Tehran')?'selected':'' }}>(UTC+03:30) Tehran</option>
              <option value='Asia/Muscat' {{ (Auth::user()->timezone == 'Asia/Muscat')?'selected':'' }}>(UTC+04:00) Abu Dhabi</option>
              <option value='Asia/Baku' {{ (Auth::user()->timezone == 'Asia/Baku')?'selected':'' }}>(UTC+04:00) Baku</option>
              <option value='Europe/Moscow' {{ (Auth::user()->timezone == 'Europe/Moscow')?'selected':'' }}>(UTC+04:00) Moscow</option>
              <option value='Asia/Muscat' {{ (Auth::user()->timezone == 'Asia/Muscat')?'selected':'' }}>(UTC+04:00) Muscat</option>
              <option value='Europe/Moscow' {{ (Auth::user()->timezone == 'Europe/Moscow')?'selected':'' }}>(UTC+04:00) St. Petersburg</option>
              <option value='Asia/Tbilisi' {{ (Auth::user()->timezone == 'Asia/Tbilisi')?'selected':'' }}>(UTC+04:00) Tbilisi</option>
              <option value='Asia/Yerevan' {{ (Auth::user()->timezone == 'Asia/Yerevan')?'selected':'' }}>(UTC+04:00) Yerevan</option>
              <option value='Asia/Kabul' {{ (Auth::user()->timezone == 'Asia/Kabul')?'selected':'' }}>(UTC+04:30) Kabul</option>
              <option value='Asia/Karachi' {{ (Auth::user()->timezone == 'Asia/Karachi')?'selected':'' }}>(UTC+05:00) Islamabad</option>
              <option value='Asia/Karachi' {{ (Auth::user()->timezone == 'Asia/Karachi')?'selected':'' }}>(UTC+05:00) Karachi</option>
              <option value='Asia/Tashkent' {{ (Auth::user()->timezone == 'Asia/Tashkent')?'selected':'' }}>(UTC+05:00) Tashkent</option>
              <option value='Asia/Calcutta' {{ (Auth::user()->timezone == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Chennai</option>
              <option value='Asia/Kolkata' {{ (Auth::user()->timezone == 'Asia/Kolkata')?'selected':'' }}>(UTC+05:30) Kolkata</option>
              <option value='Asia/Calcutta' {{ (Auth::user()->timezone == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Mumbai</option>
              <option value='Asia/Calcutta' {{ (Auth::user()->timezone == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) New Delhi</option>
              <option value='Asia/Calcutta' {{ (Auth::user()->timezone == 'Asia/Calcutta')?'selected':'' }}>(UTC+05:30) Sri Jayawardenepura</option>
              <option value='Asia/Katmandu' {{ (Auth::user()->timezone == 'Asia/Katmandu')?'selected':'' }}>(UTC+05:45) Kathmandu</option>
              <option value='Asia/Almaty' {{ (Auth::user()->timezone == 'Asia/Almaty')?'selected':'' }}>(UTC+06:00) Almaty</option>
              <option value='Asia/Dhaka' {{ (Auth::user()->timezone == 'Asia/Dhaka')?'selected':'' }}>(UTC+06:00) Astana</option>
              <option value='Asia/Dhaka' {{ (Auth::user()->timezone == 'Asia/Dhaka')?'selected':'' }}>(UTC+06:00) Dhaka</option>
              <option value='Asia/Yekaterinburg' {{ (Auth::user()->timezone == 'Asia/Yekaterinburg')?'selected':'' }}>(UTC+06:00) Ekaterinburg</option>
              <option value='Asia/Rangoon' {{ (Auth::user()->timezone == 'Asia/Rangoon')?'selected':'' }}>(UTC+06:30) Rangoon</option>
              <option value='Asia/Bangkok' {{ (Auth::user()->timezone == 'Asia/Bangkok')?'selected':'' }}>(UTC+07:00) Bangkok</option>
              <option value='Asia/Bangkok' {{ (Auth::user()->timezone == 'Asia/Bangkok')?'selected':'' }}>(UTC+07:00) Hanoi</option>
              <option value='Asia/Jakarta' {{ (Auth::user()->timezone == 'Asia/Jakarta')?'selected':'' }}>(UTC+07:00) Jakarta</option>
              <option value='Asia/Novosibirsk' {{ (Auth::user()->timezone == 'Asia/Novosibirsk')?'selected':'' }}>(UTC+07:00) Novosibirsk</option>
              <option value='Asia/Hong_Kong' {{ (Auth::user()->timezone == 'Asia/Hong_Kong')?'selected':'' }}>(UTC+08:00) Beijing</option>
              <option value='Asia/Chongqing' {{ (Auth::user()->timezone == 'Asia/Chongqing')?'selected':'' }}>(UTC+08:00) Chongqing</option>
              <option value='Asia/Hong_Kong' {{ (Auth::user()->timezone == 'Asia/Hong_Kong')?'selected':'' }}>(UTC+08:00) Hong Kong</option>
              <option value='Asia/Krasnoyarsk' {{ (Auth::user()->timezone == 'Asia/Krasnoyarsk')?'selected':'' }}>(UTC+08:00) Krasnoyarsk</option>
              <option value='Asia/Kuala_Lumpur' {{ (Auth::user()->timezone == 'Asia/Kuala_Lumpur')?'selected':'' }}>(UTC+08:00) Kuala Lumpur</option>
              <option value='Australia/Perth' {{ (Auth::user()->timezone == 'Australia/Perth')?'selected':'' }}>(UTC+08:00) Perth</option>
              <option value='Asia/Singapore' {{ (Auth::user()->timezone == 'Asia/Singapore')?'selected':'' }}>(UTC+08:00) Singapore</option>
              <option value='Asia/Taipei' {{ (Auth::user()->timezone == 'Asia/Taipei')?'selected':'' }}>(UTC+08:00) Taipei</option>
              <option value='Asia/Ulan_Bator' {{ (Auth::user()->timezone == 'Asia/Ulan_Bator')?'selected':'' }}>(UTC+08:00) Ulaan Bataar</option>
              <option value='Asia/Urumqi' {{ (Auth::user()->timezone == 'Asia/Urumqi')?'selected':'' }}>(UTC+08:00) Urumqi</option>
              <option value='Asia/Irkutsk' {{ (Auth::user()->timezone == 'Asia/Irkutsk')?'selected':'' }}>(UTC+09:00) Irkutsk</option>
              <option value='Asia/Tokyo' {{ (Auth::user()->timezone == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Osaka</option>
              <option value='Asia/Tokyo' {{ (Auth::user()->timezone == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Sapporo</option>
              <option value='Asia/Seoul' {{ (Auth::user()->timezone == 'Asia/Seoul')?'selected':'' }}>(UTC+09:00) Seoul</option>
              <option value='Asia/Tokyo' {{ (Auth::user()->timezone == 'Asia/Tokyo')?'selected':'' }}>(UTC+09:00) Tokyo</option>
              <option value='Australia/Adelaide' {{ (Auth::user()->timezone == 'Australia/Adelaide')?'selected':'' }}>(UTC+09:30) Adelaide</option>
              <option value='Australia/Darwin' {{ (Auth::user()->timezone == 'Australia/Darwin')?'selected':'' }}>(UTC+09:30) Darwin</option>
              <option value='Australia/Brisbane' {{ (Auth::user()->timezone == 'Australia/Brisbane')?'selected':'' }}>(UTC+10:00) Brisbane</option>
              <option value='Australia/Canberra' {{ (Auth::user()->timezone == 'Australia/Canberra')?'selected':'' }}>(UTC+10:00) Canberra</option>
              <option value='Pacific/Guam' {{ (Auth::user()->timezone == 'Pacific/Guam')?'selected':'' }}>(UTC+10:00) Guam</option>
              <option value='Australia/Hobart' {{ (Auth::user()->timezone == 'Australia/Hobart')?'selected':'' }}>(UTC+10:00) Hobart</option>
              <option value='Australia/Melbourne' {{ (Auth::user()->timezone == 'Australia/Melbourne')?'selected':'' }}>(UTC+10:00) Melbourne</option>
              <option value='Pacific/Port_Moresby' {{ (Auth::user()->timezone == 'Pacific/Port_Moresby')?'selected':'' }}>(UTC+10:00) Port Moresby</option>
              <option value='Australia/Sydney' {{ (Auth::user()->timezone == 'Australia/Sydney')?'selected':'' }}>(UTC+10:00) Sydney</option>
              <option value='Asia/Yakutsk' {{ (Auth::user()->timezone == 'Asia/Yakutsk')?'selected':'' }}>(UTC+10:00) Yakutsk</option>
              <option value='Asia/Vladivostok' {{ (Auth::user()->timezone == 'Asia/Vladivostok')?'selected':'' }}>(UTC+11:00) Vladivostok</option>
              <option value='Pacific/Auckland' {{ (Auth::user()->timezone == 'Pacific/Auckland')?'selected':'' }}>(UTC+12:00) Auckland</option>
              <option value='Pacific/Fiji' {{ (Auth::user()->timezone == 'Pacific/Fiji')?'selected':'' }}>(UTC+12:00) Fiji</option>
              <option value='Pacific/Kwajalein' {{ (Auth::user()->timezone == 'Pacific/Kwajalein')?'selected':'' }}>(UTC+12:00) International Date Line West</option>
              <option value='Asia/Kamchatka' {{ (Auth::user()->timezone == 'Asia/Kamchatka')?'selected':'' }}>(UTC+12:00) Kamchatka</option>
              <option value='Asia/Magadan' {{ (Auth::user()->timezone == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) Magadan</option>
              <option value='Pacific/Fiji' {{ (Auth::user()->timezone == 'Pacific/Fiji')?'selected':'' }}>(UTC+12:00) Marshall Is.</option>
              <option value='Asia/Magadan' {{ (Auth::user()->timezone == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) New Caledonia</option>
              <option value='Asia/Magadan' {{ (Auth::user()->timezone == 'Asia/Magadan')?'selected':'' }}>(UTC+12:00) Solomon Is.</option>
              <option value='Pacific/Auckland' {{ (Auth::user()->timezone == 'Pacific/Auckland')?'selected':'' }}>(UTC+12:00) Wellington</option>
              <option value='Pacific/Tongatapu' {{ (Auth::user()->timezone == 'Pacific/Tongatapu')?'selected':'' }}>(UTC+13:00) Nuku'alofa</option>

            </select>
          </div>
          <div class="form-group">
            <label for="layout">{{ trans('settings.layout') }}</label>
            <select class="form-control" name="layout" id="layout">
              <option value='false' {{ (Auth::user()->fluid_container == 'false')?'selected':'' }}>{{ trans('settings.layout_small') }}</option>
              <option value='true' {{ (Auth::user()->fluid_container == 'true')?'selected':'' }}>{{ trans('settings.layout_big') }}</option>
            </select>
          </div>

          <!--currency for user-->
          <div class="form-group">
            <label for="layout">{{ trans('settings.currency') }}</label>
            @include('partials.components.currency-select',['selectionID'=>Auth::user()->currency_id ])
          </div>

          <button type="submit" class="btn btn-primary">{{ trans('settings.save') }}</button>
        </form>

        <div class="settings-delete">
          <a href="/settings/delete" onclick="return confirm('{{ trans('settings.delete_notice') }}')">{{ trans('settings.delete_cta') }}</a>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
