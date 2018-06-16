{{-- Version check --}}

@if (config('monica.check_version'))

    @if (\App\Models\Instance\Instance::first()->latest_version != config('monica.app_version'))
    <li>
        <a href="#showVersion" data-toggle="modal" class="badge badge-success">{{ trans('app.footer_new_version') }}</a>
    </li>
    @endif

    <!-- Modal -->
    <div class="modal show-version fade" id="showVersion" tabindex="-1">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ trans('app.footer_modal_version_whats_new') }}</h5>
            <button type="button" class="close {{ \App\Helpers\LocaleHelper::getDirection() }}" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
          <p>{{ trans_choice('app.footer_modal_version_release_away', \App\Models\Instance\Instance::first()->number_of_versions_since_current_version, ['number' => \App\Models\Instance\Instance::first()->number_of_versions_since_current_version]) }}</p>
          {!! \App\Models\Instance\Instance::first()->latest_release_notes !!}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.close') }}</button>
          </div>
        </div>
      </div>
    </div>

@endif
