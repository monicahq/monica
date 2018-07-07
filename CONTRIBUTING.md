## Contributing

First off, thank you for considering contributing to Monica. We need people like
you to make Monica the best tool it can be.

Before you do anything else, please read the [README.md](readme.md) of this project first.
This is where we highlight the vision and the strategy. Please make sure you
accept this vision before contributing to this project.

If you want to contribute to the translation / localization of Monica, please
head over to [Crowdin](https://crowdin.com/project/monicahq) where we manage our
localization files.

### 1. Where do I go from here?

If you've noticed a bug or have a question, [make an issue](https://github.com/monicahq/monica/issues/new),
we'll try to answer it as fast as possible.

### 2. Fork & create a branch

If this is something you think you can fix, then
[fork Monica](https://help.github.com/articles/fork-a-repo)
and create a branch with a descriptive name.

A good branch name would be (where issue #325 is the ticket you're working on):

```sh
git checkout -b 325-add-japanese-translations
```

### 3. Get the test suite running

Make sure you follow the [instructions](https://github.com/monicahq/monica/blob/master/docs/contribute/index.md#testing-environment)
on how to setup the test suite.

### 4. Did you find a bug?

* **Ensure the bug was not already reported** by searching on GitHub under
[Issues](https://github.com/monicahq/monica/issues).

* If you're unable to find an open issue addressing the problem,
[open a new one](https://github.com/monicahq/monica/issues/new).
Be sure to include a **title and clear description**, as much relevant
information as possible, and a **code sample** or an **executable test case**
demonstrating the expected behavior that is not occurring.

### 5. Implement your fix or feature

* At this point, you're ready to make your changes! Feel free to ask for help;
everyone is a beginner at first :smile_cat:
* Write a good commit message. To write good commit messages, please follow
[those recommendations](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html).
There are important to maintain an healthy commit logs.
* If there are multiple commits in your pull request, these commits will be
squashed before merging. Please make sure, if that's the case, that your pull
request has a nice description explaining what it does.
* It's okay to have work-in-progress pull requests. Add `[WIP]` in the title of
your pull request if that's the case, otherwise your pull request will be
considered in a state of being able to be merged as is.
* If you wish to appear as a contributor, update the CONTRIBUTORS file and
add your name to it. Include this change in your pull request.

### 6. Wait for the code to be reviewed

It can take several days before we can review the code you've submitted. We
all have a lot of work to do and while we truly appreciate pull requests that
are submitted, we can't review them instantly. We'll do our best to review
them as fast as possible, but there are only 24 hours in a day and we can't
sometimes be as fast as we wish we were. Moreover, there are little chances that
the PR will be reviewed over the weekend, a time dedicated to spend time with
friends and families (those you manage with Monica anyway :-)).

Also, keep in mind that this project is still a side project. Maintainers of
this project are not paid to work on it. Everything they do, is done during
their time off of their "real" job, that means at night, on the weekend and
during holidays.

### 7. What can I contribute to?

Even the simplest change is appreciated. It can be a typo error, translating the
application in a new language, fix a bug. No change is too small.

* If your contribution involves a change in the UI (even if it's very small),
please ping @djaiss in an issue *before* you start working on it, explaining
what you want to achieve, why and how. We want to maintain a high level of
visual quality in the software and we will dismiss all pull requests that change
the front end that have not been discussed before-hand.
