{{--
  One block of the record, in one of its two states.

  Reading is the resting state. A page of forty inputs is a page nobody can read,
  and looking somebody up is the thing this screen is for far more often than
  changing what it says about them. So each block draws what it holds, and offers
  a button that turns that one block into a form.

  The block is its own region. Opening it, cancelling it and saving it each
  replace this panel and nothing else, which is how two blocks can be open at the
  same time: a response carrying every block in its resting state only ever has
  the one this panel asked for taken out of it.

  The merge is a replacement rather than the morph the rest of the product
  defaults to. Reading and editing are not two states of the same markup, they
  are a list of sentences and a form; morphing one onto the other is how a value
  somebody abandoned survives into the next reading of the page.

  @var array<string, mixed> $section
  @var \App\ViewModels\Vaults\ContactViewModel $viewModel
--}}
<x-panel
  :id="$section['regionId']"
  x-merge="replace"
  :title="$section['name']"
  :icon="$section['icon']"
  flush
  class="[&[aria-busy]]:opacity-60"
>
  <x-slot:action>
    @if ($section['isEditing'])
      {{--
        The header says which block is open, since the page can have two of them
        open at once and the buttons at the foot of a long form are not always on
        the screen at the same time as the heading they belong to.
      --}}
      <span class="text-xs tracking-caption text-terracotta-deep uppercase">{{ __('Editing') }}</span>
    @elseif ($section['isEditable'])
      <x-button.secondary
        size="small"
        :href="$section['editUrl']"
        :x-target="$section['regionId']"
      >{{ __('Edit') }}</x-button.secondary>
    @endif
  </x-slot:action>

  @if ($section['isEditing'])
    {{--
      Saving aims at two regions: this panel, and the facts beside the
      photograph. A field the template marks as highlighted is drawn up there as
      well, so writing it and replacing only the panel would leave the header
      stating the old answer.
    --}}
    <x-form
      method="put"
      :action="$section['saveUrl']"
      :upload="$section['holdsAFile']"
      :x-target="$section['regionId'].' contact-facts'"
      class="flex flex-col gap-5 bg-page p-4.5"
    >
      {{--
        The refusals that are about the vault rather than about one input land
        here as a sentence. Which control they belong to is not always the one
        somebody just typed in, so they are said once at the top of the block.
      --}}
      @if ($errors->has('fields'))
        <x-notice tone="danger">{{ $errors->first('fields') }}</x-notice>
      @endif

      @forelse ($section['fields'] as $field)
        <x-field-input
          :field="$field['field']"
          :value="$field['rawValue']"
          :formatted="$field['value']"
          :rows="$field['rows']"
          :error="$errors->get('fields.'.$field['id'])"
        />
      @empty
        <p class="text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
      @endforelse

      <div class="flex flex-wrap items-center gap-3 border-t border-hairline pt-3.5">
        <p class="max-w-[34ch] text-xs text-pretty text-muted">{{ $section['saveLine'] }}</p>

        <div class="ml-auto flex items-center gap-2">
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
    <dl class="grid gap-0">
      @forelse ($section['fields'] as $field)
        <div class="grid grid-cols-[140px_minmax(0,1fr)] items-baseline gap-4 border-b border-hairline px-4.5 py-3 last:border-b-0 max-[560px]:grid-cols-1 max-[560px]:gap-0.5">
          <dt class="text-sm text-muted">{{ $field['label'] }}</dt>

          <dd class="m-0 min-w-0">
            <x-field-value
              :field="$field['field']"
              :value="$field['rawValue']"
              :formatted="$field['value']"
              :rows="$field['rows']"
            />
          </dd>
        </div>
      @empty
        <p class="px-4.5 py-3 text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
      @endforelse
    </dl>
  @endif
</x-panel>
