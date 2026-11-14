{{-- The addresses waiting for an account while registration is closed, and the way to let them in. --}}
{{-- @var \App\ViewModels\Admin\PreviewListViewModel $viewModel --}}
<x-admin-layout :title="__('Waiting list')">
  <x-page-header :title="__('Waiting list')" :description="__('Addresses left by visitors while registration is closed. Admitting one emails a link that lets that person, and nobody else, create an account.')" />

  @error('previewList')
    <x-notice tone="danger">{{ $message }}</x-notice>
  @enderror

  @if (! $viewModel->isEnabled())
    <x-notice tone="warning">
      {{ __('The waiting list is switched off, so visitors cannot join it. Entries already here can still be admitted.') }}
      <x-link :href="route('admin.configuration.index')">{{ __('Open the configuration') }}</x-link>
    </x-notice>
  @endif

  @if ($viewModel->waitingCount() > 0)
    <x-panel :title="__('Admit the oldest')" :subtitle="trans_choice('[0,*]:count addresses are waiting. The oldest are admitted first.', $viewModel->waitingCount())">
      <x-form method="post" :action="route('admin.previewList.oldestAdmissions.create')" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <x-input
          type="number"
          id="count"
          :label="__('How many')"
          :value="old('count', 10)"
          min="1"
          max="100"
          :error="$errors->get('count')"
          required
        />

        <x-button type="submit">{{ __('Admit them') }}</x-button>
      </x-form>
    </x-panel>
  @endif

  <x-list-panel
    :items="$viewModel->rows()"
    id="preview-list"
    :paginator="$viewModel->entries()"
    :size="$viewModel->size()"
    :default="$viewModel->defaultSize()"
  >
    <x-slot:empty>
      <x-empty-state :title="__('Nobody is on the list')">
        {{ __('Visitors can join it from the root address while registration is closed and the waiting list is switched on.') }}
      </x-empty-state>
    </x-slot:empty>

    @foreach ($viewModel->rows() as $row)
      <div class="flex flex-col gap-2 border-b border-hairline px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
        <div class="grow">
          <p class="font-semibold text-ink">{{ $row['email'] }}</p>

          <p class="text-sm text-muted">
            {{ $row['joined'] }}
            @if ($row['admitted'] !== null)
              ⋅ {{ $row['admitted'] }}
            @endif
          </p>
        </div>

        <x-badge>{{ $row['state'] }}</x-badge>

        @if ($row['admitLabel'] !== null)
          <x-form method="post" :action="$row['admitUrl']">
            <x-button.secondary type="submit" size="small">{{ $row['admitLabel'] }}<span class="sr-only"> {{ $row['email'] }}</span></x-button.secondary>
          </x-form>
        @endif

        <x-delete-action
          :title="__('Remove :email from the waiting list?', ['email' => $row['email']])"
          :confirm="__('Remove')"
          :action="$row['destroyUrl']"
          :label="__('Remove')"
          size="small"
        >
          <p>{{ __('A link already sent to this address stops working. No account is touched.') }}</p>
        </x-delete-action>
      </div>
    @endforeach
  </x-list-panel>
</x-admin-layout>
