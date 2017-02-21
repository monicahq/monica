<footer>
  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
      <div class="col-xs-12">
        <ul class="horizontal">
          <li>{{ trans('app.footer_remarks') }} <a href="mailto:regis@monicahq.com">{{ trans('app.footer_send_email') }}</a></li>
          <li><a href="https://monicahq.com/privacy">{{ trans('app.footer_privacy') }}</a></li>
          <li><a href="https://tinyletter.com/monicahq">{{ trans('app.footer_newsletter') }}</a></li>
          <li><a href="https://monicahq.com/changelog">{{ trans('app.footer_release') }}</a></li>
        </ul>
      </div>
    </div>
  </div>
</footer>
