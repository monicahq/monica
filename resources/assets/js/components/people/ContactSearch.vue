<template>
    <div>
        <p class="mb2" :class="{ b: required }" v-if="title">{{ title }}</p>
        <!--<input type="hidden" :name="name" :value="selected ? selected.id : ''">-->
        <vAutocomplete :items="items" 
                        v-model="item"
                        :get-label="getLabel"
                        :component-item='template'
                        @update-items="updateItems"
                        :wait="wait"
                        :min-len="min-len"
                        class="form-control header-search-input"
                        >
        </vAutocomplete>
    </div>
</template>

<script>
    import axios from 'axios'
    import vAutocomplete from 'v-autocomplete'
    import ContactItem from './ContactItem.vue'
    export default {
        props: {
            name: {
                type: String
            },
            title: {
                type: String
            },
            required: {
                type: Boolean
            },
            userContactId: {
                type: String
            },
            defaultOptions : {
                type: Array
            },
            placeholder : {
                type: String
            },
            wait : {
                type: Number,
                default: 500
            },
            minLen : {
                type: Number,
                default: 1
            }
        },
        data () {
            return {
                src : '/people/search',
                item: {id: 0,complete_name: ''},
                items: [],
                template: ContactItem
            }
        },
        methods: {
            getLabel (item) {
                return item.complete_name
            },
            updateItems (text) {
                this.getContacts(text, this).then( (response) => {
                    this.items = response
                })
            },
            getContacts: function (keyword, vm) {
                return axios.post(this.src, {
                    needle: keyword
                }).then(function(response) {
                    let data = [];
                    response.data.data.forEach(function (contact) {
                        if (contact.id === vm.userContactId) {
                            return;
                        }
                        data.push(contact);
                    });
                    return data;
                });
            }
        },
        components: {
            vAutocomplete
        }
    }
</script>
