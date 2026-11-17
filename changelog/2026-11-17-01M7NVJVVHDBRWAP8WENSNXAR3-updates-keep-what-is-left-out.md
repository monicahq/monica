---
id: 01M7NVJVVHDBRWAP8WENSNXAR3
title: Changing a record through the API keeps what you leave out
type: improvement
area: API
published_at: 2026-11-17T22:56:01Z
link: /docs/api/changelog
---
Every change made through the API or by an assistant now follows one rule. A field you do not send keeps its value, and a field you send empty is cleared. Before, some changes wiped what was left out, so changing one thing could lose another.

An integration that cleared a field by leaving it out now sends it empty instead.
