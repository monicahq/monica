{{--
  The values a row does not own. Every row saves the whole set, so a row that
  sent only its own value would blank the others. Naming the one it owns is
  enough to carry the rest.

  @var \App\ViewModels\Settings\Account\Preferences\PreferencesViewModel $viewModel
  @var string $owns
--}}
@unless($owns === 'locale')
  <input type="hidden" name="locale" value="{{ $viewModel->locale() }}" />
@endunless

@unless($owns === 'time_format')
  <input type="hidden" name="time_format" value="{{ $viewModel->timeFormat()->value }}" />
@endunless

@unless($owns === 'date_format')
  <input type="hidden" name="date_format" value="{{ $viewModel->dateFormat()?->value }}" />
@endunless

@unless($owns === 'number_format')
  <input type="hidden" name="number_format" value="{{ $viewModel->numberFormat()?->value }}" />
@endunless
