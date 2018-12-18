function Search(form, input, resultsContainer, showResults) {
    const search = {
        form: form,
        input: input,
        resultsContainer: resultsContainer,
        timeoutId: undefined,
        accountId: $('body').attr("data-account-id")
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
    function showNoResults() {
        showResults([], search);
    }

    function parseResults(data) {
        let results = [];
        data.forEach(function (contact) {
            let person = {};
            person.id = contact.id;
            person.url = `/people/${contact.hash_id}`;

            person.name = contact.complete_name;

            // Figure out which avatar to use and create the appropriate HTML.
            person.avatar = getAvatar(contact);

            results.push(person);
        });

        return results;
    }

    function getAvatar(contact) {
        let avatar;

        if (contact.information.avatar.has_avatar) {
            avatar = `<img src="${contact.information.avatar.avatar_url}" class="avatar">`;
        } else {
            avatar = `<div class="avatar avatar-initials" style="background-color: ${contact.information.avatar.default_avatar_color}">${contact.initials}</div>`;
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
                needle: needle,
                accountId: search.accountId
            }
        }).done(function (data) {
            if (!data.data) {
                showNoResults();
                return;
            }
            const results = parseResults(data.data);
            showResults(results, search);
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
            }, 150);
        });
    }
}

const HeaderSearch = Search(
    $('.header-search > form'),
    $('.header-search-input'),
    $('.header-search-results'),
    function(results, search) {
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

        if (results.length == 0) {
            html = `
                <li class="header-search-result">
                    <div class="avatar avatar-initials" style="background-color: #fdb660;">+</div>
                    <a href="/people/add">Add new person<span /></a>
                </li>
            `;
        }

        search.resultsContainer.empty();
        search.resultsContainer.append(html);
    }
);

const multiUserInput = $('.user-input');
if (multiUserInput.length > 0) {
    const UserInputSearch = Search(
        $('.user-input > form'),
        $('.user-input-search-input'),
        $('.user-input-search-results'),
        function (results, search) {
            let html = '';
            results.forEach(function (result) {
                html += `
                <li class="header-search-result" data-contact="${result.id}" data-name="${result.name}">
                ${result.avatar}
                <a href="#">${result.name}<span /></a>
                </li>
            `;
            });

            if (results.length == 0) {
                html = `
                <li class="header-search-result">
                    <div class="avatar avatar-initials" style="background-color: #fdb660; color: #fdb660;">.</div>
                    <a href="#">No results found<span /></a>
                </li>
                `;
            }
            search.resultsContainer.empty();
            search.resultsContainer.append(html);
        }
    );
}

$('.user-input-search-results').on( "click", ".header-search-result", function() {
    let t = $(this);

    // Make sure this isn't a duplicate or a not found message 
    if (!t.data('contact') || $(`.contacts-list input[value="${t.data('contact')}"]`).length) {
        return false;
    }

    // If it's not, append to our list
    $('.contacts-list').append(`
        <li class="pretty-tag"><a href="/people/${t.data('contact')}">${t.data('name')}</a></li>
        <input type="hidden" name="contacts[]" value="${t.data('contact')}" />
    `);
});

$('.contacts-list').on('click', 'li', function(e) {
    e.preventDefault();
    $(this).next('input').remove();
    $(this).remove();
    return false;
});
