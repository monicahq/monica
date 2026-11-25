## Call paths: the shape of the change, not the code

**Show what the change reaches**, so a reviewer sees the blast radius before any code is written and implement starts at the right files. Skip it at `simple` size; a change inside one function or file gets one sentence instead.

Add a `## Call paths` section to `plan.md`: one block per behaviour that crosses functions or files, at most 3 blocks of 12 lines, at most one `note:` line under each.

```calls <the behaviour, in a few words>
  entryPoint() @ src/path.ts:40
~   changedFunction() @ src/path.ts:88
+   newFunction() @ src/path.ts:120
+     newHelper() **new** @ src/new-file.ts
-   removedFunction() @ src/old.ts:12
```
note: <one plain line, if needed>

Column 0 is the mark: `+` new, `~` changed, `-` removed, a space for unchanged. Two spaces a level. `**new**` marks a file that does not exist yet, never a new function in a file that does. Names only: no bodies, signatures or pseudo-code. **Cite only lines you read in this run**; a `+` cites the line it goes after, and a path you could not verify drops its `:line`.
