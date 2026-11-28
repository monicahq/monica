{{-- One block of the record, in one of its two states. --}}
{{--
  @var array<string, mixed> $section
  @var \App\ViewModels\Vaults\ContactViewModel $viewModel
--}}
<x-panel
  :id="$section['regionId']"
  x-merge="replace"
  :title="$section['name']"
  :icon="$section['icon']"
  :tint="$section['tint']"
  flush
  class="dim-when-busy"
>
  <x-slot:action>
    @if ($section['isEditing'])
      <x-caption tone="muted">{{ __('Editing') }}</x-caption>
    @elseif ($section['isEditable'])
      <x-button.secondary
        size="small"
        :href="$section['editUrl']"
        :x-target="$section['regionId']"
      >{{ __('Edit') }}</x-button.secondary>
    @endif
  </x-slot:action>

  @if ($section['isEditing'])
    <x-form
      method="put"
      :action="$section['saveUrl']"
      :upload="$section['holdsAFile']"
      :x-target="$section['regionId'].' contact-facts'"
      class="flex flex-col gap-5 bg-page p-4.5"
    >
      @if ($errors->has('fields'))
        <x-notice tone="danger">{{ $errors->first('fields') }}</x-notice>
      @endif

      @forelse ($section['fields'] as $field)
        <x-field-input
          :field="$field['field']"
          :value="$field['rawValue']"
          :formatted="$field['value']"
          :rows="$field['rows']"
          :reminder="$field['reminder']"
          :messages="$errors"
          :error="$errors->get('fields.'.$field['id'])"
          :vault="$viewModel->vault()"
        />
      @empty
        <p class="text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
      @endforelse

      <div class="flex flex-wrap items-center gap-3 border-t border-hairline pt-3.5">
        <p class="max-w-reading-narrow text-xs text-pretty text-muted">{{ $section['saveLine'] }}</p>

        <div class="ms-auto flex items-center gap-2">
          <x-button.secondary
            size="small"
            :href="$viewModel->pageUrl()"
            :x-target="$section['regionId']"
          >{{ __('Cancel') }}</x-button.secondary>

          <x-button type="submit" size="small">{{ __('Save this section') }}</x-button>
        </div>
      </div>
    </x-form>
  @else
    <x-detail-list label-width="140px">
      @forelse ($section['fields'] as $field)
        @if ($field['notes'] !== null)
          @include('app.vaults.contacts._notes', ['field' => $field, 'section' => $section])
        @elseif ($field['rowsMore'] !== null)
          <x-detail-list.row :label="$field['label']" value-class="">
            @include('app.vaults.contacts._field-rows', [
              'field' => $field['field'],
              'rows' => $field['rows'],
              'notes' => null,
              'more' => $field['rowsMore'],
            ])
          </x-detail-list.row>
        @else
          <x-detail-list.row :label="$field['label']" value-class="">
            <x-field-value
              :field="$field['field']"
              :value="$field['rawValue']"
              :formatted="$field['value']"
              :rows="$field['rows']"
              :reminder="$field['reminderMark']"
            />
          </x-detail-list.row>
        @endif
      @empty
        <p class="px-4.5 py-3 text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
      @endforelse
    </x-detail-list>
  @endif
</x-panel>
