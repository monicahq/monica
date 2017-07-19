function Search() {
    const search = {
        form: $('.header-search > form'),
        input: $('.header-search-input'),
        resultsContainer: $('.header-search-results'),
        timeoutId: undefined
    };

    search.init = function () {
        // Add CSRF token to AJAX requests.
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        bindEvents();
    };

    search.init();

    /* -----------------------------------------------------------------------------------
     | Search-related functions.
     |---------------------------------------------------------------------------------- */
    function showResults(results) {
        let html = '';
        results.forEach(function (result) {
            // The span is styled to cover the whole <li>, providing a clickable area over the whole result.
            html += `
                <li class="header-search-result">    
                ${result.avatar}
                <a href="${result.url}">${result.name}<span /></a>
                </li>
            `;
        });
        appendResults(html);
    }

    function showNoResults(message) {
        let html = `<li class="header-search-result" style="padding: 10px">${message}</li>`;
        appendResults(html);
    }

    function appendResults(html) {
        search.resultsContainer.empty();
        search.resultsContainer.append(html);
    }

    function parseResults(data) {
        let results = [];
        data.forEach(function (contact) {
            let person = {};
            person.url = `/people/${contact.id}`;

            const middleName = contact.middle_name || '';
            const lastName = contact.last_name || '';
            // Unify first, middle and last name in one string (depending on availability).
            person.name = contact.first_name + (middleName ? ' ' + middleName : '') + (lastName ? ' ' + lastName : '');

            // Figure out which avatar to use and create the appropriate HTML.
            person.avatar = getAvatar(contact);

            results.push(person);
        });

        return results;
    }

    function getAvatar(contact) {
        let avatar;
        if ((contact.has_avatar === 'true')) {
            avatar = `<img src="/storage/${contact.avatar_file_name}" class="avatar">`;
        } else if (contact.gravatar_url !== null) {
            avatar = `<img src="${contact.gravatar_url}" class="avatar">`;
        } else {
            let initials = contact.first_name.substring(0, 1);
            initials += contact.middle_name ? contact.middle_name.substring(0, 1) : '';
            initials += contact.last_name ? contact.last_name.substring(0, 1) : '';
            initials = initials.toUpperCase();
            avatar = `<div class="avatar avatar-initials" style="background-color: ${contact.default_avatar_color}">${initials}</div>`;
        }

        return avatar;
    }

    function getInputValue() {
        let value = search.input.eq(0).val();
        if (value === '') {
            value = search.input.eq(1).val();
        }
        return value;
    }

    function searchInContacts() {
        const needle = getInputValue();
        if(needle === '') {
            return;
        }

        $.post({
            url: "/people/search",
            data: {
                needle: needle
            }
        }).done(function (data) {
            if (data.noResults !== undefined) {
                showNoResults(data.noResults);
                return;
            }
            const results = parseResults(data);
            showResults(results);
        });
    }

    /* -----------------------------------------------------------------------------------
     | Event binding.
     |---------------------------------------------------------------------------------- */
    function bindEvents() {
        // We want typeahead-like behaviour, but not a query for every keystroke.
        // We submit a query 200ms after a key has been pressed.
        search.input.on('keyup', function () {
            if (search.timeoutId !== undefined) {
                window.clearTimeout(search.timeoutId);
            }
            search.timeoutId = window.setTimeout(searchInContacts, 200);
        });

        search.form.submit(function (e) {
            e.preventDefault();

            if (search.timeoutId !== null) {
                window.clearTimeout(search.timeoutId);
            }
            searchInContacts();
            search.input.val('');
        });

        search.input.on('focusout blur', function () {
            // We use a timeout because otherwise, in case of a click, the element is removed before the click goes through.
            // We also include the "input clear" because otherwise the results disappear slightly later, which looks odd.
            window.setTimeout(function () {
                search.input.val('');
                $('.header-search-result').remove();
            }, 50);
        });
    }
}

const search = Search();