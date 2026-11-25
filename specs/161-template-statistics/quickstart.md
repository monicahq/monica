# Quickstart: validate the feature

## Seed

A Family vault with the Animals type and templates Pet (v2, v3 published, Default), Horse (v1) and Bird (v1): 11 animals on Pet v3, 3 on Pet v2, 4 on Horse v1 (Comète, Tornado, Ulysse, Bijou), 2 archived. Comète and Tornado hold a Stable name; Comète has an active "Farrier visit" reminder on a Horse date field.

## Checks

| # | Do | Expect |
| --- | --- | --- |
| 1 | Animals list, Statistics | Figures of spec User Story 1, scope line, trail |
| 2 | Click "v2 · 3 records" | List of 3, chips Template: Pet and Version: v2, each removable |
| 3 | Field completion, Pet, All versions | Microchip number 5 of 11 · 45% |
| 4 | Horse row, Change template…, Pet, Preview changes | Counts 4, 2, 2, 0, 1, 0; Stable name · 2 animals |
| 5 | Move Bijou to Bird in another tab, then Move 4 animals | 3 moved · 1 skipped, Bijou with its reason |
| 6 | Back to statistics | Horse 0, Pet 17, Bird 1 |
| 7 | Comète's version history | Revert to Horse brings Stable name and the reminder back |
| 8 | Pet row, Update to v3… | Destination locked, 3 animals |
| 9 | Width 375 px | No horizontal scroll, Statistics in the header menu |

## Volume

Seed 10,000 animals on Horse v1 with ten values each, then time the statistics page (SC-001), the preview and the batch (SC-002).

## Commands

```bash
php artisan test --compact --filter=TemplateChange
```

```bash
php artisan test --compact --filter=RecordStatistics
```
