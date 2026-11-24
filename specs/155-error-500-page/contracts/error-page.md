# UI contract: the 500 page

What the page guarantees to a reader, a browser and assistive technology. The tests assert these and
nothing about the look.

| Element | Contract |
| --- | --- |
| Status | 500 for every page request that fails on the server |
| Title | `Server error`, via the shell |
| Heading | One `h1`: `We’re sorry, something went wrong on our end.` |
| Line | `Please try again in a moment.` |
| Try again | An `<a>`. `href` is the failed address for `GET`/`HEAD`, otherwise the previous address, falling back to the home route. Its icon is `aria-hidden` |
| Return home | An `<a>` to `route('home.index')`, secondary style |
| Illustrations | Two `<img>` with `alt=""`, one per colour scheme, never both visible |
| Footer | The shared error page footer, unchanged |
| Never present | The exception message, a stack trace, a file path, a query |
| Turbo | The page carries `data-turbo-track="reload"`, like every error page |
| JSON requests | Unchanged: they keep the JSON error |
