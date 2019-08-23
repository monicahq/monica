<div class="blank-people-state">
    <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">
        <div class="col-12">
            @if (! is_null($tags))
                <p class="clear-filter">
                {{ trans('people.people_list_filter_tag') }}
                @foreach ($tags as $tag)
                    <span class="pretty-tag">
                    {{ $tag->name }}
                    </span>
                @endforeach
                <a href="{{ route('people.index') }}">{{ trans('people.people_list_clear_filter') }}</a>
                </p>
            @endif
            @if ($tagLess)
                <p class="clear-filter">
                <span class="mr2">{{ trans('people.people_list_filter_untag') }}</span>
                <a href="{{ route('people.index') }}">{{ trans('people.people_list_clear_filter') }}</a>
                </p>
            @endif

        <div class="illustration-blank">
            <img src="img/people/blank.svg" alt="Image">
        </div>
        <h3>{{ trans('people.people_list_blank_title') }}</h3>

        <div class="cta-blank">
            <a href="{{ route('people.create') }}" class="btn btn-primary" id="button-add-contact">{{ trans('people.people_list_blank_cta') }}</a>
        </div>

        <div class="mb4">
            @if ($hasArchived)
            <a href="{{ route('people.archived') }}">@lang('people.list_link_to_archived_contacts')</a>
            @endif
        </div>
        </div>
    </div>
    </div>
</div>
