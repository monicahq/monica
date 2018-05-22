<template>
    <TypeAhead
            v-model="data"
            src="/people/search"
            :getResponse="getResponse"
            :onHit="onHit"
            :highlighting="highlighting"
            :render="onRender"
            :fetch="fetch"
    ></TypeAhead>

</template>

<script>
    import TypeAhead from 'vue2-bootstrap4-typeahead'
    import axios from 'axios'
    export default {
        name: 'header-search',
        data () {
            return {
                msg: 'Welcome to Vue2-typeahead',
                data: '',
                showConfig: false,
                selectFirst: false,
                limit: 9999,
                queryParamName: ':keyword',
                minChars: 2,
                delayTime: 500,
                placeholder: 'Please input something',
                classes: 'typeahead'
            }
        },
        methods: {
            getResponse: function (response) {
                let data = [];
                response.data.data.forEach(function (contact) {
                    const middleName = contact.middle_name || '';
                    const lastName = contact.last_name || '';
                    contact.name = contact.first_name + (middleName ? ' ' + middleName : '') + (lastName ? ' ' + lastName : '');
                    data.push(contact);
                });

                return data;
            },
            onHit: function (item, vue, index) {
                vue.query = vue.data[index].name
            },
            highlighting: function (item, vue) {
                return item.toString().replace(vue.query, `<b>${vue.query}</b>`);
                // return '';
            },
            onRender: function (items, vue) {
                let results = this.prepareResult(items);
                results = this.prepareHTML(results);
                return results;
            },
            prepareHTML: function(persons) {
                let html = [];
                persons.forEach(function(person) {
                    html.push(`
                        <li class="header-search-result">
                        ${person.avatar}
                        ${person.name}
                        </li>
                    `);
                });

                return html;
            },
            prepareResult: function(items) {
                let results = [];
                let that = this;
                items.forEach(function (contact) {
                    let person = {};
                    person.id = contact.id;
                    person.url = `/people/${contact.hash}`;

                    // Unify first, middle and last name in one string (depending on availability).
                    person.name = contact.name;

                    // Figure out which avatar to use and create the appropriate HTML.
                    person.avatar = that.getAvatar(contact);

                    results.push(person);
                });

                return results;
            },
            getAvatar: function(contact) {
                let avatar;

                if ((contact.has_avatar && contact.avatar_file_name !== null)) {
                    avatar = `<img src="/storage/${contact.avatar_file_name}" class="avatar">`;
                    console.log("here");
                } else if (contact.gravatar_url !== null) {
                    avatar = `<img src="${contact.gravatar_url}" class="avatar">`;
                } else if (contact.avatar_external_url !== null ) {
                    avatar = `<img src="${contact.avatar_external_url}" class="avatar">`;
                } else {
                    let initials = contact.first_name.substring(0, 1);
                    initials += contact.middle_name ? contact.middle_name.substring(0, 1) : '';
                    initials += contact.last_name ? contact.last_name.substring(0, 1) : '';
                    initials = initials.toUpperCase();
                    avatar = `<div class="avatar avatar-initials" style="background-color: ${contact.default_avatar_color}">${initials}</div>`;
                }

                return avatar;
            },
            fetch: function (url) {
                return axios.post(url, {
                    needle: this.data,
                    accountId: $('body').attr("data-account-id")
                },{
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                })
            }
        },
        components: {
            TypeAhead
        }
    }
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
    h1, h2 {
        font-weight: normal;
    }

    a {
        color: #42b983;
    }

    #config {
        position: absolute;
        right: 50px;
        display: flex;
        flex-direction: column;
        text-align: left;
    }

    .item {
        display: inline-block;
    }
</style>
<style>
    .typeahead {
        margin: 0 auto;
        width: 50%;
        height: 22px;
    }

    .dropdown-menu-list {
        position : relative!important;
    }
</style>