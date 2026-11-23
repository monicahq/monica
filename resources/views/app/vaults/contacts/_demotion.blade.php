{{-- The panel on a full contact's edit page offering to make them a partial contact. --}}
{{-- @var array{title: string, lead: string, lines: list<array{icon: string, text: string}>, url: string, confirmTitle: string, confirmBody: string, reachabilityLine: string|null, refusal: string|null} $demotion --}}
<x-panel :title="$demotion['title']" :subtitle="$demotion['lead']" icon="profile">
  <div class="flex flex-wrap items-center gap-x-6 gap-y-4">
    <ul class="flex min-w-60 flex-1 flex-col gap-2 text-sm text-body">
      @foreach ($demotion['lines'] as $line)
        <li class="flex gap-2">
          <x-icon :name="$line['icon']" :size="16" :stroke-width="1.5" class="mt-0.5 shrink-0 text-muted" />
          <span>{{ $line['text'] }}</span>
        </li>
      @endforeach
    </ul>

    @if ($demotion['refusal'] !== null)
      <p id="contact-demotion-refusal" class="w-full text-sm text-pretty text-body">{{ $demotion['refusal'] }}</p>

      <x-button.secondary type="button" aria-disabled="true" aria-describedby="contact-demotion-refusal" class="max-sm:w-full">
        {{ __('Make a partial contact') }}
      </x-button.secondary>
    @else
      <x-modal.form
        :title="$demotion['confirmTitle']"
        :description="trim($demotion['confirmBody'].' '.($demotion['reachabilityLine'] ?? ''))"
        :confirm="__('Make a partial contact')"
        :action="$demotion['url']"
        method="put"
      >
        <input type="hidden" name="partial" value="1" />

        <x-slot:trigger>
          <x-button.secondary type="button" @click="open = true" :aria-describedby="$errors->has('partial') ? 'contact-demotion-error' : null" class="max-sm:w-full">
            {{ __('Make a partial contact') }}
          </x-button.secondary>
        </x-slot:trigger>
      </x-modal.form>
    @endif
  </div>

  @error('partial')
    <p id="contact-demotion-error" role="alert" class="mt-3 text-sm text-pretty text-error">{{ $message }}</p>
  @enderror
</x-panel>
