<template>
    <div>
        <p class="mb2" :class="{ b: required }" v-if="title">{{ title }}</p>
        <input type="hidden" :name="name" :value="selected ? selected.id : ''">
        <vSelect :placeholder="this.placeholder" :label="'complete_name'" @search="search" :options="computedOption" v-model="selected"></vSelect>
    </div>
</template>

<script>
    import vSelect from 'vue-select'
    import axios from 'axios'
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
            }
        },
        data () {
            return {
                src : '/people/search',
                filterable : false,
                selected: null,
                newOptions: [],
            }
        },
        computed: {
            computedOption : function() {
                return this.newOptions.length > 0 ? this.newOptions : this.defaultOptions;
            }
        },
        methods: {
            search(keyword, loading) {
                this.getContacts(keyword, loading, this);
            },
            getContacts: function (keyword, loading, vm) {
                axios.post(this.src, {
                    needle: keyword,
                    accountId: $('body').attr("data-account-id")
                }).then(function(response) {
                    let data = [];
                    response.data.data.forEach(function (contact) {
                        if (contact.id === vm.userContactId) {
                            return;
                        }
                        data.push(contact);
                    });

                    vm.newOptions = data;
                });
            }
        },
        components: {
            vSelect
        }
    }
</script>
