<footer class="w-100">
  <div class="tc">
    <ul class="horizontal">
      <li class="di">{{ trans('app.footer_remarks') }} <a href="mailto:regis@monicahq.com">{{ trans('app.footer_send_email') }}</a></li>
      <li class="di"><a href="https://monicahq.com/privacy">{{ trans('app.footer_privacy') }}</a></li>
      <li class="di"><a href="https://tinyletter.com/monicahq">{{ trans('app.footer_newsletter') }}</a></li>
      <li class="di"><a href="https://monicahq.com/changelog">{{ trans('app.footer_release') }}</a></li>
      <li class="di"><a href="https://github.com/monicahq/monica">{{ trans('app.footer_source_code') }}</a></li>
      <li class="di">{{ trans('app.footer_version', ['version' => config('monica.app_version')]) }}</li>

      @include('partials.check')
    </ul>
  </div>
</footer>
