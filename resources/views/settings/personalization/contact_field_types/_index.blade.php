<h3 class="with-actions">
  {{ trans('settings.personalization_contact_field_type_title') }}
  <a href="#addContactFieldType" class="btn" data-toggle="modal">{{ trans('settings.personalization_contact_field_type_add') }}</a>
</h3>
<p>{{ trans('settings.personalization_contact_field_type_description') }}</p>

<div class="dt dt--fixed w-100 collapse br--top br--bottom">
  <div class="dt-row">
    <div class="dtc">
      <div class="pa2 b">
        {{ trans('settings.personalization_contact_field_type_table_name') }}
      </div>
    </div>
    <div class="dtc">
      <div class="pa2 b">
        {{ trans('settings.personalization_contact_field_type_table_protocol') }}
      </div>
    </div>
    <div class="dtc tr">
      <div class="pa2 b">
        {{ trans('settings.personalization_contact_field_type_table_actions') }}
      </div>
    </div>
  </div>

  @foreach (auth()->user()->account->contactFieldTypes as $contactFieldType)

    <div class="dt-row bb b--light-gray">
      <div class="dtc">
        <div class="pa2">
          <i class="{{ $contactFieldType->fontawesome_icon }} pr2"></i>
          {{ $contactFieldType->name }}
        </div>
      </div>
      <div class="dtc">
        <code class="f7">{{ $contactFieldType->protocol }}</code>
      </div>
      <div class="dtc tr">
        <div class="pa2">
          <a href="#" onclick="if (confirm('{{ trans('settings.personalization_contact_field_type_delete_confirmation') }}')) { $(this).closest('.dt-row').find('.entry-delete-form').submit(); } return false;">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>
      </div>

      <form method="POST" action="{{ action('Settings\\PersonalizationController@destroyContactFieldType', compact('contactFieldType')) }}" class="entry-delete-form hidden">
        {{ method_field('DELETE') }}
        {{ csrf_field() }}
      </form>
    </div>

  @endforeach

  @include('settings.personalization.contact_field_types._modal_add_contact_field_type')

</div>
