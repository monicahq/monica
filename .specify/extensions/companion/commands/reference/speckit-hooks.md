# Stock spec-kit extension hooks

Read this only when `.specify/extensions.yml` registers hooks for the running step.

Companion runs **on top of** stock spec-kit, so a project's installed spec-kit **extensions** (git, and any others in `.specify/extensions.yml`) must fire on a Companion run exactly as on a stock `/speckit.*` run. That is separate from Companion's own node-hooks in `.specify/companion.yml`; both fire. Like the rest of the pipeline this must **never fail the host command**: a missing file or an anchor with no entries is skipped silently, and an unreadable file is reported once, then the run continues.

Let `<step>` be this command's phase: `specify`, `plan`, `tasks`, or `implement`. Run the pass twice: `hooks.before_<step>` **now, before any of the work below**, and `hooks.after_<step>` once this command's work is fully reported, before handing off.

- **Read `.specify/extensions.yml`.** Absent, or carrying no entries for that anchor: skip silently. If the YAML cannot be parsed or is invalid, do not skip silently: tell the user that `.specify/extensions.yml` could not be read (include the parser error) and that no hooks were checked, including any mandatory (`optional: false`) hooks registered there, then continue normally. Skip a hook that is `enabled: false` (no `enabled` field means enabled), and any hook whose `extension` is `companion`: those record a stock run's lifecycle, which this command records in its own body, so dispatching them would rewrite what this step just wrote. A hook with a non-empty `condition` is left to the HookExecutor and never evaluated by you; no condition, or a null or empty one, is executable.
- **Emit one block per executable hook.** An optional hook (`optional: true`):

  ```
  ## Extension Hooks

  **Optional Pre-Hook**: {extension}
  Command: `/{command}`
  Description: {description}

  Prompt: {prompt}
  To execute: `/{command}`
  ```

  A mandatory hook (`optional: false`) instead:

  ```
  ## Extension Hooks

  **Automatic Pre-Hook**: {extension}
  Executing: `/{command}`
  EXECUTE_COMMAND: {command}

  Wait for the result of the hook command before proceeding to the Outline.
  ```

  Those labels are the **before** pass's. In the **after** pass drop `Pre-` from the label and drop the closing wait line.

For `specify`, branch creation is normally one of these `before_specify` hooks (the git extension); the spec directory and its files are always created by the command body itself.
