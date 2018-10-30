<style scoped>
</style>

<template>
    <div class="">
        <div class="mt4">
            <a @click="editMode = true" v-show="!editMode">Edit tag</a>
            <a @click="editMode = false" v-show="editMode">Cancel</a>
            <a @click="editMode = false" v-show="editMode">Save</a>
        </div>

        <input type="text"
                class="mt7 mb5"
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
        <ul>
            <li v-for="tag in contactTags" :key="tag.id">
                <span>{{ tag.name }}</span>
                <a @click="removeTag(tag)" v-show="editMode">remove tag</a>
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
                this.filterAllTags()
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
                this.filterAllTags()
            },

            filterResults() {
                this.results = this.availableTags.filter(item => item.name.toLowerCase().indexOf(this.search.toLowerCase()) > -1)
            },

            filterAllTags() {
                this.availableTags = this.allTags.filter((item) => {
                    return !this.contactTags.includes(item)
                })
            }
        }
    }
</script>
