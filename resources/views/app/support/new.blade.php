{{-- Where somebody asks the people running the instance for help: what about, and what is going on. --}}
{{-- @var \App\ViewModels\Support\SupportRequestFormViewModel $viewModel --}}
<x-support.page
  :title="__('New request')"
  current="new"
  :open-count="$viewModel->navigation()->openRequestCount()"
  :crumbs="[['label' => __('Support'), 'url' => route('support.requests.index')], ['label' => __('New request')]]"
>
  <div class="mx-auto w-full max-w-3xl space-y-10" x-data="{ category: @js($viewModel->selectedCategory()) }">
    <header class="space-y-3">
      <h1 class="sr-only">{{ __('New request') }}</h1>
      <p class="text-4xl leading-tight font-semibold tracking-tight text-ink">{{ __('Hi there!') }}</p>
      <p class="text-base text-body text-pretty">{{ __('Whether you’ve found a bug, have an idea, or simply need a hand, you’ve come to the right place.') }}</p>
      <p class="text-base text-body">{{ __('Every message is read by a real person.') }}</p>
      <p class="text-base text-body">{{ __('Actually, by us, Regis and Alexis') }}</p>

      <div class="flex items-center gap-4 rounded-lg border border-hairline bg-card p-5">
        <div class="flex shrink-0 -space-x-2.5" aria-hidden="true">
          @foreach ($viewModel->portraitUrls() as $portraitUrl)
            <x-image
              :src="$portraitUrl"
              :width="48"
              :height="48"
              loading="eager"
              class="size-12 rounded-full bg-sunken object-cover ring-2 ring-card"
            />
          @endforeach
        </div>

        <div class="min-w-0 space-y-0.5">
          <p class="text-base font-semibold text-ink">{{ $viewModel->administratorNames() }}</p>
          <p class="text-sm text-muted text-pretty">{{ $viewModel->administratorLine() }}</p>
        </div>
      </div>
    </header>

    <x-form method="post" :action="route('support.requests.create')" :upload="true" class="space-y-10">
      <fieldset class="space-y-4" @if ($errors->has('category')) aria-describedby="category-error" @endif>
        <legend class="space-y-1">
          <span class="block text-xl font-semibold text-ink">{{ __('What do you need help with?') }}</span>
          <span class="block text-sm text-muted">{{ __('This helps us understand what you’re looking for.') }}</span>
        </legend>

        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-3">
          @foreach ($viewModel->categories() as $category)
            <label class="flex min-h-19 cursor-pointer items-center gap-3.5 rounded-lg border border-hairline bg-canvas px-5 py-4 shadow-panel transition-colors duration-(--duration-fast) ease-out hover:bg-hover has-checked:border-ink has-checked:bg-card has-checked:shadow-[inset_0_0_0_1px_var(--color-ink)] has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45">
              <input
                type="radio"
                name="category"
                value="{{ $category['value'] }}"
                x-model="category"
                class="sr-only"
                @if ($errors->has('category')) aria-invalid="true" @endif
                @checked($viewModel->selectedCategory() === $category['value'])
              />
              <span class="flex size-8 shrink-0 items-center justify-center rounded-md border border-hairline bg-card text-muted" aria-hidden="true">
                <x-icon :name="$category['icon']" :size="16" :stroke-width="1.5" />
              </span>
              <span class="min-w-0 flex-1 text-base font-medium text-ink text-pretty">{{ $category['label'] }}</span>
              <span class="flex text-ink" aria-hidden="true" x-show="category === @js($category['value'])" @if ($viewModel->selectedCategory() !== $category['value']) x-cloak @endif>
                <x-icon name="check" :size="16" :stroke-width="1.6" />
              </span>
            </label>
          @endforeach
        </div>

        <x-error id="category-error" :messages="$errors->get('category')" />
      </fieldset>

      @foreach ($viewModel->categories() as $category)
        <section
          class="space-y-4"
          aria-labelledby="topic-{{ $category['value'] }}"
          x-show="category === @js($category['value'])"
          @if ($viewModel->selectedCategory() !== $category['value']) x-cloak @endif
        >
          <h2 id="topic-{{ $category['value'] }}" class="text-2xl font-semibold text-ink">{{ $category['heading'] }}</h2>

          @foreach ($category['paragraphs'] as $paragraph)
            <p class="text-base text-body text-pretty">{{ $paragraph }}</p>
          @endforeach
        </section>
      @endforeach

      <div class="space-y-6">
        <div class="space-y-2">
          <x-input
            id="subject"
            name="subject"
            :label="__('Subject')"
            :value="old('subject')"
            :help="__('A short summary so you can spot this request later.')"
            :error="$errors->get('subject')"
            x-bind:aria-describedby="'subject-help subject-hint-' + category + ({{ $errors->has('subject') ? 'true' : 'false' }} ? ' subject-error' : '')"
            required
          />

          @foreach ($viewModel->categories() as $category)
            <p id="subject-hint-{{ $category['value'] }}" class="text-xs text-muted" x-show="category === @js($category['value'])" @if ($viewModel->selectedCategory() !== $category['value']) x-cloak @endif>{{ $category['subjectHint'] }}</p>
          @endforeach
        </div>

        <x-textarea
          id="body"
          name="body"
          :label="__('What’s your question, comment, or issue?')"
          :value="old('body')"
          :rows="8"
          :help="__('Share as much detail as you can. The more context you provide, the easier it is to help.')"
          :error="$errors->get('body')"
          required
        />

        <x-support.file-input
          id="files"
          :accept="$viewModel->acceptedTypes()"
          :maximum-files="$viewModel->maximumFiles()"
          :maximum-kilobytes="$viewModel->maximumKilobytes()"
          :error="array_merge($errors->get('files'), $errors->get('files.*'))"
        />
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
        <x-button>
          {{ __('Send message') }}
          <x-icon name="arrow-right" :size="15" :stroke-width="1.6" />
        </x-button>
        <p class="text-xs text-muted">{{ __('We’ll reply as soon as we can.') }}</p>
      </div>
    </x-form>
  </div>
</x-support.page>
