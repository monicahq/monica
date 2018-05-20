<template>
    <div>
        <p class="mb2" v-bind:class="{ b: required }" v-if="title">{{ title }}</p>
        <input type="hidden" :name="name" :value="contactIdSelected"/>
        <TypeAhead
                v-model="data"
                src="/people/search"
                :getResponse="getResponse"
                :onHit="onHit"
                :highlighting="highlighting"
                :render="onRender"
                :fetch="fetch"
        ></TypeAhead>
    </div>
</template>

<script>
    import TypeAhead from 'vue2-bootstrap4-typeahead'
    import axios from 'axios'
    export default {
        name: 'add-exist-relationship-typeahead',
        props: {
            name: {
                type: String,
            },
            title: {
                type: String,
            },
            required: {
                type: Boolean,
            }
        },
        data () {
            return {
                msg: 'Add relationship from exist',
                data: '',
                showConfig: false,
                selectFirst: false,
                limit: 20,
                queryParamName: ':keyword',
                minChars: 2,
                delayTime: 500,
                classes: 'typeahead',
                contactIdSelected : '',
                userContactId : ''
            }
        },
        methods: {
            getResponse: function (response) {
                let data = [];
                let that = this;
                if (response.data.data) {
                    response.data.data.forEach(function (contact) {
                        // Exclude myself
                        if (contact.hash === that.userContactId) {
                            return;
                        }

                        let middleName = contact.middle_name || '';
                        let lastName = contact.last_name || '';
                        contact.name = contact.first_name + (middleName ? ' ' + middleName : '') + (lastName ? ' ' + lastName : '');
                        data.push(contact);
                    });
                }

                return data;
            },
            onHit: function (item, vue, index) {
                vue.query = vue.data[index].name;
                this.contactIdSelected = vue.data[index].id;
            },
            highlighting: function (item, vue) {
                let escapes = vue.query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                let regExp = new RegExp(escapes, 'ig');
                return item.toString().replace(regExp, `<b>${vue.query}</b>`);
            },
            onRender: function (items, vue) {
                let results = this.prepareResult(items);
                results = this.prepareHTML(results);
                return results;
            },
            prepareHTML: function(persons) {
                let html = [];

                persons.forEach(function(person) {
                    html.push(`<li>${person.name}</li>`);
                });

                return html;
            },
            prepareResult: function(items) {
                return items;
            },
            fetch: function (url) {
                return axios.post(url, {
                    needle: this.data,
                    accountId: $('body').attr("data-account-id")
                },{
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            }
        },
        components: {
            TypeAhead
        }
    }
</script>

<style>
    .typeahead {
        margin: 0 auto;
        width: 50%;
        height: 22px;
    }

    .dropdown-menu-list {
        position : relative!important;
    }

    .dropdown-item {
        text-decoration: none!important;
    }
</style>