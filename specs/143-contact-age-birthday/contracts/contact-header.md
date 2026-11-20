# Contract: the line under the contact’s name

Screen: `resources/views/app/vaults/contacts/show.blade.php`, every page of a contact.

- The region `id="contact-facts"` (`x-merge="replace"`) wraps the line and the list of facts, in that
  order, so a section save that targets `contact-facts` redraws both.
- The line is a `<p>` holding the sentences of the state table in `data-model.md`, with a muted `·`
  between age and countdown, marked `aria-hidden="true"`.
- The countdown carries a gift icon, decorative (`aria-hidden`), in ink and semibold; the age is in
  body colour. Sizes and tokens follow design 1b; the markup reuses `x-icon`.
- When the view model returns no line, no element is rendered.
- The view model exposes `birthdayLine(): ?array{age: ?string, upcoming: ?string}`; both strings are
  already translated.
