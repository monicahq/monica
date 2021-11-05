@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => Str::of(config('app.url'))->ltrim('/')])
            {{ config('app.display_name') }}
        @endcomponent
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.display_name') }}. @lang('All rights reserved.')
        @endcomponent
    @endslot
@endcomponent
