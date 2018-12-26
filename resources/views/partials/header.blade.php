<header class="bg-white dn db-m db-l">
  <div class="ph3 pv2 w-100">
		<div class="cf">
			<div class="fl w-10 pa2">
				<a class="relative header-logo" href="{{ route('dashboard.index') }}">
          <img src="/img/logo/logo.svg" width="39" height="35" />
        </a>
			</div>
			<div class="fl w-80 tc">
				<ul>
					<li class="di {{ htmldir() == 'rtl' ? 'ml3' : 'mr3' }} header-menu-item pa2">
						<a class="b no-underline no-color" href="{{ route('dashboard.index') }}">
							<img class="relative {{ htmldir() == 'rtl' ? 'pl1' : 'pr1' }}" src="/img/header/icon-home.svg" />
							{{ trans('app.main_nav_home') }}
						</a>
					</li>
					<li class="di {{ htmldir() == 'rtl' ? 'ml3' : 'mr3' }} header-menu-item pa2">
						<a class="b no-underline no-color" href="{{ route('people.index') }}">
							<img class="relative {{ htmldir() == 'rtl' ? 'pl1' : 'pr1' }}" src="/img/header/icon-contacts.svg" />
							{{ trans('app.main_nav_people') }}
						</a>
					</li>
					<li class="di {{ htmldir() == 'rtl' ? 'ml3' : 'mr3' }} header-menu-item pa2">
						<a class="b no-underline no-color" href="{{ route('journal.index') }}">
							<img class="relative {{ htmldir() == 'rtl' ? 'pl1' : 'pr1' }}" src="/img/header/icon-journal.svg" />
							{{ trans('app.main_nav_journal') }}
						</a>
					</li>
					<li class="di {{ htmldir() == 'rtl' ? 'ml3' : 'mr3' }} header-menu-item pa2">
						<a class="b no-underline no-color" href="">
							<img class="relative {{ htmldir() == 'rtl' ? 'pl1' : 'pr1' }}" src="/img/header/icon-find.svg" />
							{{ trans('app.main_nav_find') }}
						</a>
					</li>
				</ul>
			</div>
			<div class="fl w-10 pa2 tr relative header-menu-settings">
        <header-menu></header-menu>
			</div>
		</div>
	</div>
</header>

{{-- MOBILE MENU --}}
<header class="bg-white mobile dn-ns">
  <div class="ph3 pv2 w-100 relative">
		<div class="pa2 relative menu-toggle">
      <label for="menu-toggle" class="dib b relative">Menu</label>
      <input type="checkbox" id="menu-toggle">
      <ul class="list pa0 mt4 mb0" id="mobile-menu">
        <li class="pv2 bt b--light-gray">
          <a class="no-color b no-underline" href="{{ route('dashboard.index') }}">
              {{ trans('app.main_nav_home') }}
          </a>
        </li>
        <li class="pv2 bt b--light-gray">
          <a class="no-color b no-underline" href="{{ route('people.index') }}">
              {{ trans('app.main_nav_people') }}
          </a>
        </li>
        <li class="pv2 bt b--light-gray">
          <a class="no-color b no-underline" href="{{ route('journal.index') }}">
              {{ trans('app.main_nav_journal') }}
          </a>
        </li>
        <li class="pv2 bt b--light-gray">
          <a class="no-color b no-underline" href="">
              {{ trans('app.main_nav_find') }}
          </a>
        </li>
        <li class="pv2 bt b--light-gray">
          <a class="no-color b no-underline" href="">
              {{ trans('app.main_nav_changelog') }}
          </a>
        </li>
        <li class="pv2 bt b--light-gray">
          <a class="no-color b no-underline" href="">
              {{ trans('app.main_nav_settings') }}
          </a>
        </li>
        <li class="pv2 bt b--light-gray">
          <a class="no-color b no-underline" href="">
              {{ trans('app.main_nav_signout') }}
          </a>
        </li>
      </ul>
    </div>
    <div class="absolute pa2 header-logo">
      <a href="{{ route('dashboard.index') }}">
          <img src="/img/logo/logo.svg" width="30" height="27" />
      </a>
    </div>
	</div>
</header>
