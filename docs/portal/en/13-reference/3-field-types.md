---
id: reference.field-types
title: Field types
description: See every kind of field a template can hold, from short text to emails and phone numbers, and what each one stores.
slug: field-types
section: reference
last_updated: 2026-09-29
---

# Field types

Every kind of field you can add to a template, by the name the builder shows. @doc(templates.change) explains how to add one.

## Fields that hold an answer

| Type | What it holds | Notes |
| --- | --- | --- |
| **Short text** | One line of text, up to a length you set | |
| **Long text** | Several lines, for anything that needs a paragraph | |
| **Number** | A number, with its own minimum, maximum and decimals | |
| **Duration** | A length of time, typed in minutes and shown in weeks, days, hours and minutes | From one minute to one year. Something that comes back, such as a visit every three months, is a reminder |
| **Yes or no** | A single checkbox | |
| **URL** | A web address | Checked before it is saved |
| **Email** | An email address, and what it is for, such as **Home** or **Work** | Checked before it is saved. Can identify somebody, to spot duplicates |
| **Phone** | A phone number, stored as written, and what it is for | Can identify somebody, to spot duplicates |
| **Address** | Street, city, postcode and country in one block, and what it is for | |
| **Dropdown** | One option from a value list, in a closed menu | Needs a value list |
| **Radio buttons** | One option from a value list, all visible at once | Needs a value list. Best under five options |
| **Multiple choice** | Several options from the same value list | Needs a value list |
| **Date** | A precise day | Can drive a reminder |
| **Recurring date** | A day that comes back every year, such as a birthday | The year may be unknown. Can drive a reminder |
| **Record relation** | A pointer to another record, such as a pet’s caregiver | You choose what kind of record it may point at |
| **File** | Any upload, limited to the file types you accept | Counts toward storage |
| **Image** | An upload shown as an image on the record | Counts toward storage |
| **Profile photo** | The single photo shown at the top of the record | Counts toward storage |

## Fields that group or lay out

| Type | What it does |
| --- | --- |
| **Repeater** | A group of fields filled again and again, such as several emails or several caregivers. A repeater cannot sit inside another repeater |
| **Heading** | A title inside a section |
| **Divider** | A line between two groups of fields |
| **Help text** | A note to whoever fills in the section |

## Options every field has

- **Required**: the section cannot be saved without it.
- **Sensitive**: hidden on screen until revealed, and left out of sharing links unless included. See @doc(concepts.privacy).
- **Highlighted**: shown in the header of the record, as well as in its section.
- **Help text** and **Placeholder**: guidance shown to whoever fills it in.

A field’s type is fixed once added. To change it, remove the field and add another.

## Not available yet

The builder also lists types marked **Later**, which cannot be picked yet: **Free tags**, **Qualified relation**, **Money**, **Full name**, **Rating**, **Rich text**, **Colour**, **Geographic location** and **Computed value**.
