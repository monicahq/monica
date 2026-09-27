{{-- The answers a deadline may take today, each recorded in one press and never changed after. --}}
{{-- @var \App\ViewModels\Vaults\ReminderRow $row --}}
@if ($row->canRemember())
  <x-form method="put" :action="$row->answerUrl()" class="contents">
    <input type="hidden" name="answer" value="remembered" />

    <x-button.secondary type="submit" size="small">
      {{ __('I remembered') }}<span class="sr-only">: {{ $row->text() }}</span>
    </x-button.secondary>
  </x-form>
@endif

@if ($row->canForget())
  <x-form method="put" :action="$row->answerUrl()" class="contents">
    <input type="hidden" name="answer" value="forgotten" />

    <x-button.secondary type="submit" size="small">
      {{ __('I forgot') }}<span class="sr-only">: {{ $row->text() }}</span>
    </x-button.secondary>
  </x-form>
@endif
