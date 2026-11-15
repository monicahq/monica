---
name: view-models
description: Conventions for defining view models. Use when the user wants to create or modify view models, including their structure, relationships, and naming conventions.
---

# View models

- View models are classes responsible for preparing data for the view. They MUST NOT contain any business logic, but they MAY contain presentation logic.
- You MUST create a view model for every view that needs data to be displayed, except when the data is really minimal.
- View models MUST be stored in the `app/ViewModels` folder, and MUST be grouped by major domains of the application (`Admin`, `Auth`, `Marketplace`, `Settings`, `Shared`, `Vaults` and so on), like controllers and views. They MUST match the controller folder structure.
- View models MUST be named after the view they are used for, with the suffix `ViewModel`. For example, the list of contacts of a vault is drawn from `Vaults/ContactsViewModel`.

## Structure for simple pages

You MUST write a single view model per page, in a single file. Read
`app/ViewModels/Vaults/ContactsViewModel.php` before writing one: the constructor takes what the
screen needs as promoted `private readonly` properties, and each public method returns one thing the
view prints. The controller builds it and passes it to the view as `viewModel`, as
`App\Vaults\ContactController` does, and the view reads it and nothing else.

## Structure for complex pages

For a rich page such as a dashboard, you MUST write one main view model for the page, and you MUST
delegate the complex blocks to small dedicated objects next to it, the way `MarketplaceViewModel`
returns a `MarketplaceStatistics` from `statistics()`. The controller MUST only know about the main
view model.
