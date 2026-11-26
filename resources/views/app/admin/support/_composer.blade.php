{{-- Where an administrator writes: a reply that goes to the user, or a note only administrators read. --}}
{{--
  @var array{uuid: string, firstName: string, email: string} $current
  @var list<array{value: string, label: string}> $statusOptions
--}}
<section
  aria-labelledby="composer-title"
  x-data="{ mode: @js($errors->note->any() ? 'note' : 'reply') }"
  class="rounded-lg border shadow-panel"
  :class="mode === 'note' ? 'border-warning-edge bg-warning-tint' : 'border-hairline bg-canvas'"
>
  <h3 id="composer-title" class="sr-only">{{ __('Write') }}</h3>

  <div class="flex gap-1 border-b border-inherit px-2 pt-2">
    <button type="button" @click="mode = 'reply'" :aria-pressed="(mode === 'reply').toString()" class="flex cursor-pointer items-center gap-2 border-b-2 px-3 pt-2 pb-2.5 text-sm font-medium focus-ring-inset" :class="mode === 'reply' ? 'border-ink text-ink' : 'border-transparent text-muted'">
      <x-icon name="mail" :size="15" :stroke-width="1.5" />
      {{ __('Reply to :name', ['name' => $current['firstName']]) }}
    </button>
    <button type="button" @click="mode = 'note'" :aria-pressed="(mode === 'note').toString()" class="flex cursor-pointer items-center gap-2 border-b-2 px-3 pt-2 pb-2.5 text-sm font-medium focus-ring-inset" :class="mode === 'note' ? 'border-warning text-warning' : 'border-transparent text-muted'">
      <x-icon name="lock" :size="15" :stroke-width="1.5" />
      {{ __('Internal note') }}
    </button>
  </div>

  <div x-show="mode === 'reply'">
    <x-form method="post" :action="route('admin.support.replies.create', ['supportRequest' => $current['uuid']])" class="space-y-3 p-4">
      <x-textarea
        id="reply-body"
        name="body"
        :label="__('Reply')"
        :rows="5"
        :value="$errors->note->any() ? null : old('body')"
        :help="__('Sent to :email and shown in their requests, signed with your name.', ['email' => $current['email']])"
        :error="$errors->get('body')"
        :placeholder="__('Write your reply…')"
        described-by="reply-body-markdown"
        required
      />

      <x-support.markdown-hint id="reply-body-markdown" />

      <div class="flex flex-wrap items-end gap-3">
        <x-button>{{ __('Send reply') }}</x-button>

        <x-select id="reply-status" name="status" size="small" :label="__('Then move it to')" :inline="true">
          @foreach ($statusOptions as $option)
            <option value="{{ $option['value'] }}" @selected($option['value'] === 'waiting_on_user')>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
      </div>
    </x-form>
  </div>

  <div x-show="mode === 'note'" x-cloak>
    <x-form method="post" :action="route('admin.support.notes.create', ['supportRequest' => $current['uuid']])" class="space-y-3 p-4">
      <x-textarea
        id="note-body"
        name="body"
        :label="__('Internal note')"
        :rows="5"
        :value="$errors->note->any() ? old('body') : null"
        :help="__('Only administrators see this note. :name is never told it exists.', ['name' => $current['firstName']])"
        :error="$errors->note->get('body')"
        :placeholder="__('What the next administrator should know…')"
        described-by="note-body-markdown"
        required
      />

      <x-support.markdown-hint id="note-body-markdown" />

      <x-button>{{ __('Add note') }}</x-button>
    </x-form>
  </div>
</section>
