# Mutation check

Read this only when `.specify/companion.yml` sets `mutation: { enabled: true }`.

Run the project's mutation tool on the files this change touched (Stryker for JavaScript and TypeScript with the JSON reporter, mutmut for Python, then `mutmut export-cicd-stats`), and hand the report to the writer:
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --mutation-report <reports/mutation/mutation.json or mutants/mutmut-cicd-stats.json>
```
It records the caught count and marks weak each requirement whose tests caught no planted bug. Without the setting, skip this step.
