{{-- The portrait of somebody, with the star that marks them as a favourite. --}}
{{-- @var \App\ViewModels\Vaults\ContactViewModel $viewModel --}}
<div
  id="contact-favourite"
  x-merge="replace"
  class="relative shrink-0 rounded-full border-2 p-0.75 transition-colors duration-(--duration-fast) ease-out {{ $viewModel->isFavourite() ? 'border-warning-ring' : 'border-transparent' }}"
>
  <x-contact-avatar :name="$viewModel->title()" :url="$viewModel->avatarUrl()" :size="104" />

  <x-form
    method="put"
    :action="$viewModel->favouriteUrl()"
    x-target="contact-favourite"
    class="absolute -right-0.5 -bottom-0.5"
  >
    <input type="hidden" name="favourite" value="{{ $viewModel->isFavourite() ? 0 : 1 }}" />

    <button
      type="submit"
      aria-pressed="{{ $viewModel->isFavourite() ? 'true' : 'false' }}"
      aria-label="{{ $viewModel->favouriteLabel() }}"
      title="{{ $viewModel->favouriteLabel() }}"
      class="flex size-6.5 cursor-pointer items-center justify-center rounded-full shadow-panel transition-colors duration-(--duration-fast) ease-out focus-ring {{ $viewModel->isFavourite() ? 'border-2 border-canvas bg-warning text-canvas hover:bg-warning/90' : 'border border-hairline-strong bg-canvas text-muted-soft hover:bg-warning-tint hover:text-warning' }}"
    >
      <x-icon :name="$viewModel->isFavourite() ? 'star-filled' : 'star'" :size="14" :stroke-width="1.6" />
    </button>
  </x-form>
</div>
