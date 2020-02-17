First of all thanks so much for taking the time to open a pull request and help the project. It's because of people like you that we love working on this project.

Please read the list below. Feel free to delete this text after but we need you to read it so we make sure that the project is consistent and remains of quality.

### Checklist

#### Before submitting the PR
- [ ] Read the [CONTRIBUTING document](https://github.com/monicahq/monica/blob/master/CONTRIBUTING.md) before submitting your PR.
- [ ] If the PR is related to an issue or fix one, don't forget to indicate it.
- [ ] Create your PR as draft if it is not final yet. Mark it as ready... when it’s ready. Otherwise the PR will be considered complete and rejected if it's not working.

### General checks
- [ ] Make sure that the change you propose is the smallest possible.
- [ ] The name of the PR should follow the [conventional commits guideline](https://github.com/monicahq/monica/blob/master/docs/contribute/index.md#conventional-commits) that the project follows.

### Front-end changes
- [ ] If you change the UI, make sure to ask repositories administrators first about your changes by pinging djaiss or asbiin in this PR.
- [ ] Screenshots are included if the PR changes the UI.
- [ ] Front-end tests have been written with Cypress.

#### Backend/models changes
- [ ] The API has been updated.
- [ ] API's documentation has been added by submitting a pull request in the [marketing website repository](https://github.com/monicahq/marketing_site/pulls).
- [ ] Tests have been added for the new code.
- [ ] If you change a model, make sure the SetupTest file is updated. We need seeders to develop locally and generate fake data.

#### If the code changes the SQL schema
- [ ] Make sure exporting account data as SQL is still working.
- [ ] Make sure your changes do not break importing data with `vCard` and `.csv` files.
- [ ] Make sure account reset and deletion still work.

#### Other tasks
- [ ] [CHANGELOG](https://github.com/monicahq/monica/blob/master/CHANGELOG.md) entry added, if necessary, under `UNRELEASED`.
- [ ] [CONTRIBUTORS](https://github.com/monicahq/monica/blob/master/CONTRIBUTORS) entry added, if necessary.
- [ ] If it's relevant and worth mentioning, create a changelog entry for this change. The changelog entry will appear inside the UI for all users to see. To know if your change is worth the creation of a changelog entry, [read the documentation](https://github.com/monicahq/monica/blob/master/docs/administrators/tips.md#when-is-it-relevant-to-create-a-changelog-entry).
- [ ] Don't forget to [ask for a free account](mailto:regis@monicahq.com) on https://monicahq.com as anyone who contributes can request a free account.
