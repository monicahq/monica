# Contract: the facts under an animal's name

Applies to the record page of any kind (`entities.show`, added by `6d12e152`), not only animals.

- Under the title, the page lists the first three highlighted fields of the record's template version,
  in the order the version places them: page, then section, then field.
- Each entry reads its field label followed by the formatted value, or "Not recorded" when empty.
- A record whose version highlights nothing shows no list at all.
- The list is a `<ul>` of text entries; the icon beside each is decorative and hidden from assistive
  technology.
- A highlighted field past the third is not shown in the header.

For an animal on Animal: Species, Breed or mix, Date of birth. For an animal on Basic animal: Date of
birth alone, since Basic animal highlights Birthday only.
