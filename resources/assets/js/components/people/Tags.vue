<style scoped>
.tag-list-item {
}
</style>

<template>
    <div class="">
        <div class="mt4">
            <a @click="editMode = true" v-show="!editMode">Edit tag</a>
            <a @click="editMode = false" v-show="editMode">Cancel</a>
            <a @click="editMode = false" v-show="editMode">Save</a>
        </div>

        <ul>
            <li v-for="tag in contactTags" :key="tag.id" class="di mr2">
                <span class="bg-white ph2 pb1 pt0 dib br3 b--light-gray ba">
                    {{ tag.name }}
                    <span @click="removeTag(tag)" v-show="editMode" class="pointer">×</span>
                </span>
            </li>
        </ul>

        <input type="text"
                class=""
                v-show="editMode"
                v-model="search"
                @input="onChange">

        <ul class="autocomplete-results" v-show="isOpen">
            <li class="autocomplete-result"
                v-for="result in results"
                :key="result.id"
                @click="setResult(result)">
                {{ result.name }}
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                allTags: [],
                availableTags: [],
                contactTags: [],
                editMode: false,
                search: '',
                results: [],
                isOpen: false,
            };
        },

        mounted() {
            this.prepareComponent();
        },

        props: {
            hash: {
                type: String,
            },
        },

        methods: {
            prepareComponent() {
                this.getExistingTags()
                this.getContactTags()
                //this.filterAllTags()
            },

            getExistingTags() {
                axios.get('/tags')
                    .then(response => {
                        this.allTags = response.data.data
                    })
            },

            getContactTags() {
                axios.get('/people/' + this.hash + '/tags')
                    .then(response => {
                        this.contactTags = response.data.data
                    })
            },

            removeTag(tag) {
                this.contactTags.splice(this.contactTags.indexOf(tag), 1)
            },

            onChange() {
                this.isOpen = true
                this.filterResults()
            },

            setResult(result) {
                this.search = null
                this.isOpen = false
                this.contactTags.push(result)
                //this.filterAllTags()
            },

            filterResults() {
                this.results = this.allTags.filter(item => item.name.toLowerCase().indexOf(this.search.toLowerCase()) > -1)
            },

            filterAllTags() {
                var me = this.contactTags
                this.availableTags = this.allTags.filter((item) => {
                    return !me.includes(item)
                })
            },

            save() {

            }
        }
    }
</script>
