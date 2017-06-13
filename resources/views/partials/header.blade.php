<header>
  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="hidden-xs-down col-sm-6">
        <div class="logo">
          <a href="{{ route('people.index') }}">
            <img src="{{ asset('/img/small-logo.png') }}" width="40" />
          </a>
        </div>
      </div>
      <div class="hidden-xs-down col-sm-6">
        <ul class="header-nav">
          <li class="header-nav-item">
            <a href="{{ route('dashboard') }}" class="header-nav-item-link">{{ trans('app.main_nav_dashboard') }}</a>
          </li>
          <li class="header-nav-item">
            <a href="{{ route('people.index') }}" class="header-nav-item-link">{{ trans('app.main_nav_family') }}</a>
          </li>
          <li class="header-nav-item">
            <a href="{{ route('journal.index') }}" class="header-nav-item-link">{{ trans('app.main_nav_journal') }}</a>
          </li>
          <li class="header-nav-item">
            <a href="{{ route('settings.index') }}" class="header-nav-item-link">{{ trans('app.header_settings_link') }}</a>
          </li>
          <li class="header-nav-item">
            <a href="{{ route('logout') }}" class="header-nav-item-link">{{ trans('app.header_logout_link') }}</a>
          </li>
        </ul>
      </div>
    </div>

    <div class="row">
      <div class="hidden-sm-up col-xs-12">
        <ul class="mobile-menu">
          <li class="cta"><a href="{{ route('people.create') }}" class="btn btn-primary">{{ trans('app.main_nav_cta') }}</a></li>
          <li><a href="{{ route('dashboard') }}" class="header-nav-item-link">{{ trans('app.main_nav_dashboard') }}</a></li>
          <li><a href="{{ route('people.index') }}" class="header-nav-item-link">{{ trans('app.main_nav_family') }}</a></li>
          <li><a href="{{ route('settings.index') }}" class="header-nav-item-link">{{ trans('app.header_settings_link') }}</a></li>
          <li><a href="{{ route('logout') }}" class="header-nav-item-link">{{ trans('app.header_logout_link') }}</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
