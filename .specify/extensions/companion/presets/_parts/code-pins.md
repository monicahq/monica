## Code pins: a note under the line it explains

**Pin a note under a line of code only when that one line needs a note to be understood.** Most code in a plan needs none, and a plain fence stays a plain fence. Usable from the tasks step too.

Name the file on the fence: `sketch` for code that does not exist yet, `<file>:<from>-<to>` for real lines you read in this run, copied as they are. At most 12 lines a sketch and 3 pins a block. Pins go straight after the fence, and `hl` and `pin` use the numbers the card shows: from 1 in a sketch, from `<from>` in a citation.

```ts sketch src/new-file.ts hl=2
export function add(a: number, b: number) {
    return a + b;
}
```
pin 2: <one plain line on why this line matters>

```ts src/path.ts:40-42 hl=41
<lines 40 to 42 of the file, all three>
```
pin 41: <one plain line>
