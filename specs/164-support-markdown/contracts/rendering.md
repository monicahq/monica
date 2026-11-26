# Contract: what a message may become

`SupportMarkdown::toHtml()` is the only producer of message HTML on the two screens (FR-020). The
tests (research R9) assert this contract on the parsed output.

## Never written

Elements: `script`, `style`, `iframe`, `frame`, `object`, `embed`, `form`, `button`, `select`,
`textarea`, `img`, `svg`, `math`, `video`, `audio`, `link`, `meta`, `base`, `h1`, `h2`, `h3`, and any
`input` other than a disabled checkbox.

Attributes: `id`, `name`, `style`, `src`, `srcdoc`, `formaction`, any `on*`, any `x-*`, `@*`, `:*`.

## Links

- An `href` that names a scheme, read as a browser reads it (control characters and spaces removed),
  names `http`, `https` or `mailto`. A link to any other scheme is written without its address. An
  address with no scheme is relative to the instance, which is harmless.
- A link to another host carries `target="_blank"` and `rel="noopener noreferrer nofollow"`.
- An image written in Markdown is a link to its address, its alt text as the words.

## Shape

- `#` is `h4`, `##` is `h5`, deeper headings are `h6`.
- A single line break is `<br />`.
- Raw HTML is present as escaped text.
- A task list is an ordinary list: its `[ ]` and `[x]` are text, and no `input` is drawn.
- The empty string gives the empty string.

## Bounds

For any input of 20,000 characters or less, rendering finishes in under one second (SC-003).

## `SupportMarkdown::plainText()`

The rendering with every tag removed, entities decoded, whitespace collapsed to single spaces, trimmed.
