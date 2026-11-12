{{-- One group of fields of a possession type: its name, its place among the groups, and its fields. --}}
{{--
  @var array $group
  @var list<array{value: string, label: string}> $kinds
--}}
@php
  $moved = session('moved') === 'group-'.$group['uuid'];
@endphp
<section id="group-{{ $group['uuid'] }}" key="group-{{ $group['uuid'] }}" class="rounded-lg border border-hairline bg-canvas" aria-labelledby="group-{{ $group['uuid'] }}-name">
  <div class="flex flex-wrap items-center gap-2.5 rounded-t-lg border-b border-hairline bg-card px-4 py-3">
    <x-form method="put" :action="$group['updateAction']" x-target="group-{{ $group['uuid'] }} possession-type-saved" class="min-w-48 flex-1">
      <x-input
        :id="'group-'.$group['uuid'].'-name'"
        name="name"
        size="small"
        :value="$group['name']"
        :aria-label="__('Name of the group')"
        :error="$errors->getBag('group'.$group['uuid'])->get('name')"
        maxlength="255"
        required
        @change="$el.form.requestSubmit()"
      />
    </x-form>

    <span class="text-sm text-muted">{{ $group['fieldCount'] }}</span>

    <div class="flex items-center gap-1">
      @unless ($group['isFirst'])
        <x-form method="put" :action="$group['positionAction']" x-target="possession-type-fields possession-type-saved">
          <input type="hidden" name="position" value="{{ $group['position'] - 1 }}" />
          <x-icon-button type="submit" icon="chevron-up" :label="__('Move :name up', ['name' => $group['name']])" :x-autofocus="$moved && (session('direction') === 'up' || $group['isLast'])" />
        </x-form>
      @endunless

      @unless ($group['isLast'])
        <x-form method="put" :action="$group['positionAction']" x-target="possession-type-fields possession-type-saved">
          <input type="hidden" name="position" value="{{ $group['position'] + 1 }}" />
          <x-icon-button type="submit" icon="chevron-down" :label="__('Move :name down', ['name' => $group['name']])" :x-autofocus="$moved && (session('direction') === 'down' || $group['isFirst'])" />
        </x-form>
      @endunless

      <x-form method="post" :action="$group['createFieldAction']" x-target="possession-type-fields possession-type-saved">
        <input type="hidden" name="group" value="{{ $group['uuid'] }}" />
        <x-icon-button type="submit" icon="plus" :label="__('Add a field to :name', ['name' => $group['name']])" />
      </x-form>

      <x-modal.warning
        :title="__('Remove the group “:name”?', ['name' => $group['name']])"
        :confirm="__('Remove the group')"
        :cancel="__('Keep it')"
        :action="$group['destroyAction']"
        x-target="possession-type-fields possession-type-saved"
      >
        <x-slot:trigger>
          <x-icon-button icon="close" :label="__('Remove :name', ['name' => $group['name']])" align="right" @click="open = true" />
        </x-slot:trigger>

        <p>{{ __('Its fields are kept, and become standalone fields of the type.') }}</p>
      </x-modal.warning>
    </div>
  </div>

  <div class="flex flex-col gap-3 p-4">
    @forelse ($group['fields'] as $field)
      @include('app.settings.account.possession-types._field', ['field' => $field, 'kinds' => $kinds])
    @empty
      <p class="text-sm text-muted">{{ __('No fields in this group yet.') }}</p>
    @endforelse
  </div>
</section>
