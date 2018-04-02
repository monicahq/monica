### Checklist

#### Before submitting the PR
- [ ] Read the [CONTRIBUTING document](https://github.com/monicahq/monica/blob/master/CONTRIBUTING.md) before submitting your PR.
- [ ] If the PR is related to an issue or fix one, don't forget to indicate it.
- [ ] Make sure that the change you propose is the smallest possible.
- [ ] Screenshots are included if the PR changes the UI.
- [ ] If you change the UI, make sure the user experience is consistent with the current interface.

#### Code-related tasks
- [ ] Tests added for this feature/bug.
- [ ] Impact on the seeders.
- [ ] Impact on the API.

#### If the code changes the SQL schema
- [ ] Impact on account export.
- [ ] Impact on importing data with `vCard` and `.csv` files.
- [ ] Impact on account reset and deletion.

#### Other tasks
- [ ] [CHANGELOG](https://github.com/monicahq/monica/blob/master/CHANGELOG) entry added, if necessary, under `UNRELEASED`.
- [ ] [CONTRIBUTORS](https://github.com/monicahq/monica/blob/master/CONTRIBUTORS) entry added, if necessary.
- [ ] If it's relevant, add the documentation about your feature in the README file.
- [ ] Indicate `[wip]` in the title of the PR it is is not final yet. Remove `[wip]` when ready. Otherwise the PR will be considered complete and rejected if it's not working.
