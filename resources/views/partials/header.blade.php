<nav class="bg-blue-monica">
  <div class="ph3 ph5-ns pv2 cf w-100">
    <div class="mw9 center dt w-100">
      <div class="w-50 ph2 dtc v-mid">
        <a href="/dashboard" class="header-logo mr2">
          <img src="/img/monica_reverse.svg" width="40" height="43" />
        </a>
         <div class="dib w-60 header-search">
          <form role="search" method="POST" action="people/search">
            {{ csrf_field() }}
            <input type="search" placeholder="{{ trans('people.people_search') }}" class="form-control header-search-input">
          </form>
          <ul class="header-search-results"></ul>
        </div>
      </div>
      <div class="w-50 ph2 dtc v-mid">
        <div class="tr {{ \App\Helpers\LocaleHelper::getDirection() }}">
          <a href="/dashboard" class="header-nav-item-link dib">{{ trans('app.main_nav_dashboard') }}</a>
          <a href="/people" class="header-nav-item-link dib">{{ trans('app.main_nav_family') }}</a>
          <a href="/journal" class="header-nav-item-link dib">{{ trans('app.main_nav_journal') }}</a>
          <a href="/settings" class="header-nav-item-link dib">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" width="14" height="16" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 14 16"><path fill-rule="evenodd" d="M14 8.77v-1.6l-1.94-.64-.45-1.09.88-1.84-1.13-1.13-1.81.91-1.09-.45-.69-1.92h-1.6l-.63 1.94-1.11.45-1.84-.88-1.13 1.13.91 1.81-.45 1.09L0 7.23v1.59l1.94.64.45 1.09-.88 1.84 1.13 1.13 1.81-.91 1.09.45.69 1.92h1.59l.63-1.94 1.11-.45 1.84.88 1.13-1.13-.92-1.81.47-1.09L14 8.75v.02zM7 11c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z" fill="#fff"/></svg>
          </a>
          <a href="/changelog" class="header-nav-item-link dib pulse">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" width="14" height="16" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 14 16"><path fill-rule="evenodd" d="M14 12v1H0v-1l.73-.58c.77-.77.81-2.55 1.19-4.42C2.69 3.23 6 2 6 2c0-.55.45-1 1-1s1 .45 1 1c0 0 3.39 1.23 4.16 5 .38 1.88.42 3.66 1.19 4.42l.66.58H14zm-7 4c1.11 0 2-.89 2-2H5c0 1.11.89 2 2 2z" fill="#fff"/></svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</nav>
