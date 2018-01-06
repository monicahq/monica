<ul class="tags">
    <li>
        <ul class="tags-list">
            @foreach ($contact->tags as $tag)
                <li class="pretty-tag"><a href="/people?tags={{ $tag->name_slug }}">{{ $tag->name }}</a></li>
            @endforeach
        </ul>
    </li>

    <li><a href="#" class="showTagForm">{{ trans('people.tag_edit') }}</a></li>
</ul>

<form method="POST" action="/people/{{ $contact->id }}/tags/update" class="tagsForm">
    {{ csrf_field() }}
    <input name="tags" value="{{ $contact->getTagsAsString() }}" />
    <div class="tagsFormActions">
        <button type="submit" class="btn btn-primary">{{ trans('app.update') }}</button>
        <a href="#" class="btn tagsFormCancel">{{ trans('app.cancel') }}</a>
    </div>
</form>