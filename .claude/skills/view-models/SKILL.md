---
name: view-models
description: Conventions for defining view models. Use when the user wants to create or modify view models, including their structure, relationships, and naming conventions.
---

# View models

- View models are classes responsible for preparing data for the view. They MUST NOT contain any business logic, but they MAY contain presentation logic.
- You MUST create a view model for every view that needs data to be displayed, except when the data is really minimal.
- View models MUST be stored in the `app/ViewModels` folder, and MUST be grouped by major domains of the application, like `Account`, `Operate`, `Grow`, like controllers and views. They MUST match the controller folder structure.
- View models MUST be named after the view they are used for, with the suffix `ViewModel`. For example, the view model for the `account/profile.blade.php` view is `Account/ProfileViewModel`.

## Structure for simple pages

You MUST write a single view model per page, in a single file.

```
app/
└── ViewModels/
    └── Employees/
        └── EmployeeIndexViewModel.php
```

Viewmodel:
```
class EmployeeIndexViewModel
{
    public function __construct(
        private Company $company,
    ) {
    }

    public function employees(): Collection
    {
        return $this->company
            ->employees()
            ->with(['team', 'jobTitle'])
            ->orderBy('last_name')
            ->get();
    }

    public function activeCount(): int
    {
        return $this->company
            ->employees()
            ->active()
            ->count();
    }
}
```

Controller:
```
public function index(): View
{
    return view('employees.index', [
        'viewModel' => new EmployeeIndexViewModel(
            company: currentCompany(),
        ),
    ]);
}
```

View:
```
<p>{{ $viewModel->activeCount() }} employees</p>

@foreach ($viewModel->employees() as $employee)
    ...
@endforeach
```

## Structure for complex pages

For a rich page such as a dashboard, you MUST write one main view model for the page, and you MUST delegate the complex blocks to small dedicated objects.

```
app/
└── ViewModels/
    └── Dashboard/
        ├── DashboardViewModel.php
        ├── EmployeeSummary.php
        ├── UpcomingEvents.php
        ├── PendingTasks.php
        └── CompanyMetrics.php
```

The controller MUST only know about the main view model:
```
return view('dashboard.show', [
    'viewModel' => new DashboardViewModel(
        company: currentCompany(),
        user: auth()->user(),
    ),
]);
```

Main view model:
```
class DashboardViewModel
{
    public function __construct(
        private Company $company,
        private User $user,
    ) {
    }

    public function employeeSummary(): EmployeeSummary
    {
        return new EmployeeSummary($this->company);
    }

    public function upcomingEvents(): UpcomingEvents
    {
        return new UpcomingEvents(
            company: $this->company,
            user: $this->user,
        );
    }

    public function pendingTasks(): PendingTasks
    {
        return new PendingTasks(
            company: $this->company,
            user: $this->user,
        );
    }

    public function companyMetrics(): CompanyMetrics
    {
        return new CompanyMetrics($this->company);
    }
}
```
