<style lang="scss" >
  .v-autocomplete-list {
    position: absolute;
    width: 100%;
    z-index: 10;
  }
  .v-autocomplete-list-item {
    background: white;
  }
  .v-autocomplete-item-active {
    background: #f5f5f5;
  }
</style>

<template>
    <div>
        <p class="mb2" :class="{ b: required }" v-if="title">{{ title }}</p>
        <v-autocomplete :items="items" 
                        v-model="item"
                        :get-label="getLabel"
                        :component-item="template"
                        @update-items="updateItems"
                        @item-selected="click"
                        @blur="clearSearch"
                        :wait="wait"
                        :min-len="minLen"
                        :keep-open="true"
                        :input-attrs="input"
                        :autoSelectOneItem="false"
                        :keepOpen="true"
                        >
        </v-autocomplete>
    </div>
</template>

<script>
    import axios from 'axios'
    import vAutocomplete from 'v-autocomplete'
    import ContactItem from './ContactItem.vue'

    export default {
        props: {
            name: {
                type: String,
                default: '',
            },
            title: {
                type: String,
                default: null,
            },
            required: {
                type: Boolean,
                default: true,
            },
            userContactId: {
                type: String,
                default: '0',
            },
            defaultOptions : {
                type: Array,
                default: function () {
                    return []
                }
            },
            placeholder : {
                type: String,
                default: '',
            },
            wait : {
                type: Number,
                default: 600,
            },
            minLen : {
                type: Number,
                default: 1,
            }
        },

        data () {
            return {
                item: null,
                items: [],
                template: ContactItem,
                input: {
                    class: 'form-control header-search-input',
                    placeholder: this.placeholder
                }
            }
        },

        methods: {
            getLabel (item) {
                return item != null ? item.keyword : '';
            },

            updateItems (text) {
                this.getContacts(text, this).then( (response) => {
                    this.items = response
                })
            },

            getContacts: function (keyword, vm) {
                return axios.post('/people/search', {
                    needle: keyword
                }).then(function(response) {
                    let data = [];
                    if (response.data.noResults != null) {
                        data.push({
                            'item' : -1,
                            'message': response.data.noResults,
                            'keyword': keyword,
                        });
                    }
                    else {
                        response.data.data.forEach(function (contact) {
                            if (contact.id === vm.userContactId) {
                                return;
                            }
                            contact.keyword = keyword;
                            data.push(contact);
                        });
                    }
                    return data;
                });
            },

            clearSearch(sender) {
                this.item = null;
                this.items = [];
            },

            click(item) {
                if (!item) {
                    return;
                }
                if (item.id > 0) {
                    window.location = '/people/'+item.hash_id;
                } else {
                    window.location = '/people/add';
                }
            }
        },

        components: {
            vAutocomplete
        }
    }
</script>
