<template>
    <div>
        <p class="mb2" v-bind:class="{ b: required }" v-if="title">{{ title }}</p>
        <input type="hidden" :name="name" :value="selected ? selected.id : ''">
        <vSelect placeholder="search" @search="search" :options="computedOption" v-model="selected"></vSelect>
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
                selected: { label: this.placeholder, id: '' },
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
                console.log(this.options);
                this.getContacts(keyword, loading, this);
            },
            getContacts: function (keyword, loading, vm) {
                axios.post(this.src, {
                    needle: keyword,
                    accountId: $('body').attr("data-account-id")
                }).then(function(response) {
                    let data = [];
                    response.data.data.forEach(function (contact) {
                        if (contact.hash === vm.userContactId) {
                            return;
                        }

                        let middleName = contact.middle_name || '';
                        let lastName = contact.last_name || '';
                        contact.label = contact.first_name + (middleName ? ' ' + middleName : '') + (lastName ? ' ' + lastName : '');
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

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

</style>
<style>

</style>