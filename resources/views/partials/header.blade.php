<header>
  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row hidden-sm-down">
      <div class="col-sm-5">
        <div class="row">
          <div class="logo">
            <a href="/dashboard">
              <img src="/img/monica_reverse.svg" width="40" height="43" />
            </a>
          </div>
          <div class="col-sm-9 header-search">
            <form role="search" method="POST" action="people/search">
              {{ csrf_field() }}
              <input type="search" placeholder="{{ trans('people.people_search') }}" class="form-control header-search-input">
            </form>
            <ul class="header-search-results"></ul>
          </div>
        </div>
      </div>
      <div class="col-sm-7 padding-left-none">
        <ul class="header-nav">
          <li class="header-nav-item">
            <a href="/dashboard" class="header-nav-item-link">{{ trans('app.main_nav_dashboard') }}</a>
          </li>
          <li class="header-nav-item">
            <a href="/people" class="header-nav-item-link">{{ trans('app.main_nav_family') }}</a>
          </li>
          <li class="header-nav-item">
            <a href="/journal" class="header-nav-item-link">{{ trans('app.main_nav_journal') }}</a>
          </li>
          <li class="header-nav-item">
            <a href="/settings" class="header-nav-item-link">{{ trans('app.header_settings_link') }}</a>
          </li>
          <li class="header-nav-item">
            <a href="/logout" class="header-nav-item-link">{{ trans('app.header_logout_link') }}</a>
          </li>
        </ul>
      </div>
    </div>

    <div class="row">
      <div class="hidden-md-up col-xs-12">
        <div class="header-search">
          <form role="search" method="POST" action="people/search">
            {{ csrf_field() }}
            <input type="search" placeholder="{{ trans('people.people_search') }}" class="form-control header-search-input">
          </form>
          <ul class="header-search-results"></ul>
        </div>
        <ul class="mobile-menu">
          <li class="cta"><a href="/people/add" class="btn btn-primary">{{ trans('app.main_nav_cta') }}</a></li>
          <li><a href="/dashboard" class="header-nav-item-link">{{ trans('app.main_nav_dashboard') }}</a></li>
          <li><a href="/people" class="header-nav-item-link">{{ trans('app.main_nav_family') }}</a></li>
          <li><a href="/journal" class="header-nav-item-link">{{ trans('app.main_nav_journal') }}</a></li>
          <li><a href="/settings" class="header-nav-item-link">{{ trans('app.header_settings_link') }}</a></li>
          <li><a href="/logout" class="header-nav-item-link">{{ trans('app.header_logout_link') }}</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
