const TOKEN_REGEX = /\{\{\{CONTACT-ID:([a-f0-9-]+)\|([^}]*)\}\}\}/g;
const EDITOR_MENTION_REGEX = /@"([^"]+)"/g;
const DISAMBIGUATED_LABEL_REGEX = /^(.*)\(([a-f0-9]{8})\)$/i;

function normalizeName(name) {
  return `${name ?? ''}`.trim();
}

function sanitizeTokenFallbackName(name) {
  return normalizeName(name).replace(/[|}]/g, '');
}

function escapeHtml(value) {
  return `${value ?? ''}`
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function sanitizeUrl(url) {
  if (!url) {
    return '#';
  }

  const candidate = `${url}`.trim();

  if (candidate.startsWith('/') || /^https?:\/\//i.test(candidate)) {
    return escapeHtml(candidate);
  }

  return '#';
}

export function shortContactId(contactId) {
  return `${contactId ?? ''}`.slice(0, 8).toLowerCase();
}

export function buildContactMentionIndex(contacts = []) {
  const byId = new Map();
  const byName = new Map();
  const byLabel = new Map();
  const labelById = new Map();
  const byShortId = new Map();
  const nameCount = new Map();

  contacts.forEach((contact) => {
    const id = `${contact?.id ?? ''}`;
    const name = normalizeName(contact?.name);

    if (!id || !name) {
      return;
    }

    const normalizedName = name.toLowerCase();
    const shortId = shortContactId(id);

    if (!byName.has(normalizedName)) {
      byName.set(normalizedName, []);
    }
    byName.get(normalizedName).push(contact);
    byId.set(id, contact);
    nameCount.set(normalizedName, (nameCount.get(normalizedName) ?? 0) + 1);

    if (!byShortId.has(shortId)) {
      byShortId.set(shortId, []);
    }
    byShortId.get(shortId).push(contact);
  });

  byId.forEach((contact, id) => {
    const name = normalizeName(contact.name);
    const normalizedName = name.toLowerCase();
    const duplicate = (nameCount.get(normalizedName) ?? 0) > 1;
    const label = duplicate ? `${name} (${shortContactId(id)})` : name;

    byLabel.set(label, contact);
    labelById.set(id, label);
  });

  const uniqueByShortId = new Map();
  byShortId.forEach((contactsForShortId, shortId) => {
    if (contactsForShortId.length === 1) {
      uniqueByShortId.set(shortId, contactsForShortId[0]);
    }
  });

  return {
    byId,
    byName,
    byLabel,
    byShortId: uniqueByShortId,
    labelById,
  };
}

export function mentionLabelForContact(contact, mentionIndex) {
  if (!contact) {
    return '';
  }

  return mentionIndex.labelById.get(`${contact.id}`) ?? normalizeName(contact.name);
}

function parseMentionLabel(label) {
  const trimmedLabel = normalizeName(label);
  const match = trimmedLabel.match(DISAMBIGUATED_LABEL_REGEX);

  if (!match) {
    return {
      name: trimmedLabel,
      shortId: null,
    };
  }

  return {
    name: normalizeName(match[1]),
    shortId: normalizeName(match[2]).toLowerCase(),
  };
}

export function deserializeTokenizedMentions(content, mentionIndex) {
  if (!content) {
    return '';
  }

  return content.replace(TOKEN_REGEX, (_match, contactId, fallbackName) => {
    const id = `${contactId}`;
    const contact = mentionIndex.byId.get(id);
    const label = contact
      ? mentionIndex.labelById.get(id) ?? normalizeName(contact.name)
      : sanitizeTokenFallbackName(fallbackName);

    if (!label) {
      return '';
    }

    return `@"${label.replace(/"/g, '')}"`;
  });
}

export function serializeEditorMentions(content, mentionIndex) {
  if (!content) {
    return {
      content: '',
      invalidMentions: [],
    };
  }

  const invalidMentions = [];

  const serializedContent = content.replace(EDITOR_MENTION_REGEX, (_match, rawLabel) => {
    const label = normalizeName(rawLabel);

    if (!label) {
      return '';
    }

    let contact = mentionIndex.byLabel.get(label);
    const parsedLabel = parseMentionLabel(label);

    if (!contact && parsedLabel.shortId) {
      contact = mentionIndex.byShortId.get(parsedLabel.shortId);
    }

    if (!contact && parsedLabel.name) {
      const sameNameContacts = mentionIndex.byName.get(parsedLabel.name.toLowerCase()) ?? [];

      if (sameNameContacts.length === 1) {
        contact = sameNameContacts[0];
      } else if (sameNameContacts.length > 1) {
        invalidMentions.push({
          label,
          reason: 'ambiguous',
        });
        return '';
      }
    }

    if (!contact) {
      invalidMentions.push({
        label,
        reason: 'unknown',
      });
      return '';
    }

    const fallbackName = sanitizeTokenFallbackName(normalizeName(contact.name) || parsedLabel.name);

    return `{{{CONTACT-ID:${contact.id}|${fallbackName}}}}`;
  });

  return {
    content: serializedContent,
    invalidMentions,
  };
}

export function convertMentions(content, contacts = null, highlightedContact = null) {
  if (!content) {
    return '';
  }

  const contactsById = new Map(
    (contacts ?? []).map((contact) => [`${contact.id}`, contact]),
  );
  const highlightedId = highlightedContact ? `${highlightedContact}` : null;

  return content.replace(TOKEN_REGEX, (_match, contactId, fallbackName) => {
    const id = `${contactId}`;
    const contact = contactsById.get(id);

    if (contact) {
      const safeName = escapeHtml(normalizeName(contact.name));
      const safeUrl = sanitizeUrl(contact.url);

      if (highlightedId && id === highlightedId) {
        return `<a href="${safeUrl}" class="text-blue-500 hover:underline"><strong>@${safeName}</strong></a>`;
      }

      return `<a href="${safeUrl}" class="text-blue-500 hover:underline">@${safeName}</a>`;
    }

    const safeFallbackName = escapeHtml(sanitizeTokenFallbackName(fallbackName));
    if (highlightedId && id === highlightedId) {
      return `<strong>@${safeFallbackName}</strong>`;
    }

    return `@${safeFallbackName}`;
  });
}
