export function convertMentions(content, contacts = null, highlightedContact = null) {
  if (!content) return '';

  return content.replace(/\{\{\{CONTACT-ID:([a-f0-9-]+)\|(.*?)\}\}\}/g, (match, contactId, fallbackName) => {
    // If a contacts list is provided, try to find the contact.
    if (contacts) {
      let contact = contacts.find((c) => c.id === contactId);
      if (contact) {
        let contactName = contact.name.trim();
        let contactUrl = contact.url;
        // If this contact should be highlighted, wrap the name in <strong>.
        if (highlightedContact && contactId === highlightedContact) {
          return `<a href="${contactUrl}" target="_blank" class="text-blue-500 hover:underline"><strong>@${contactName}</strong></a>`;
        }
        return `<a href="${contactUrl}" target="_blank" class="text-blue-500 hover:underline">@${contactName}</a>`;
      }
    }

    // If no contacts list is provided or the contact wasn't found,
    // check if the contactId matches the highlighted contact and bold the fallback.
    if (highlightedContact && contactId === highlightedContact) {
      return `<strong>@${fallbackName}</strong>`;
    }
    return `@${fallbackName}`;
  });
}
