<style scoped>
.autocomplete-results {
    width: 150px;
}

.autocomplete-result.is-active,
  .autocomplete-result:hover {
    background-color: #4AAE9B;
    color: white;
  }
</style>

<template>
    <div class="tc">
        <div class="mb3" v-show="editMode">
            <div class="relative di mr2">
                <input type="text"
                        class="di br2 f5 ba b--black-40 pa2 outline-0"
                        v-model="search"
                        :placeholder="$t('people.tag_add_search')"
                        @keydown.down="onArrowDown"
                        @keydown.up="onArrowUp"
                        @keydown.enter="onEnter"
                        @keydown.esc="onEscape"
                        @input="onChange">

                <ul class="autocomplete-results ba b--gray-monica absolute bg-white left-0 z-9999" v-show="isOpen">
                    <li class="autocomplete-result"
                        v-for="(result, i) in results"
                        :key="i"
                        @click="setResult(result)"
                        :class="{ 'is-active': i === arrowCounter }">
                        {{ result.name }}
                    </li>
                </ul>
            </div>

            <a @click="editMode = false" class="pointer">{{ $t('app.cancel') }}</a>
            <a @click="store()" class="pointer">{{ $t('app.save_close') }}</a>
        </div>

        <ul>
            <li v-for="tag in contactTags" :key="tag.id" class="di mr2">
                <span class="bg-white ph2 pb1 pt0 dib br3 b--light-gray ba mb2">
                    <span @click="navigateTo(tag)" class="pointer" v-show="!editMode">{{ tag.name }}</span>
                    <span v-show="editMode">{{ tag.name }}</span>
                    <span @click="removeTag(tag)" v-show="editMode" class="pointer">×</span>
                </span>
            </li>
            <li class="di" v-show="contactTags.length > 0">
                <a @click="editMode = true" v-show="!editMode" class="pointer">{{ $t('app.edit') }}</a>
            </li>
            <li class="di" v-show="contactTags.length == 0">
                <span class="i mr2">{{ $t('people.tag_no_tags') }}</span><a @click="editMode = true" v-show="!editMode" class="pointer">{{ $t('people.tag_add') }}</a>
            </li>
        </ul>
    </div>
</template>

<script>
    import moment from 'moment'

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
                arrowCounter: 0,
                dirltr: true,
            };
        },

        mounted() {
            this.dirltr = this.$root.htmldir == 'ltr';
            this.prepareComponent();
            document.addEventListener('click', this.handleClickOutside)
        },

        destroyed() {
            document.removeEventListener('click', this.handleClickOutside)
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

            onEnter() {
                if (this.search != '') {
                    this.contactTags.push({
                        id: moment().format(), // we just need a random ID here
                        name: this.search
                    })
                    this.arrowCounter = -1
                    this.isOpen = false
                    this.search = null
                }
            },

            onArrowDown() {
                if (this.arrowCounter < this.results.length) {
                    this.arrowCounter = this.arrowCounter + 1;
                    this.search = this.results[this.arrowCounter].name
                }
            },

            onArrowUp() {
                if (this.arrowCounter > 0) {
                    this.arrowCounter = this.arrowCounter - 1;
                    this.search = this.results[this.arrowCounter].name
                }
            },

            onEscape() {
                this.arrowCounter = -1
                this.isOpen = false
                this.search = null
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

            store() {
                this.editMode = false
                axios.post('/people/' + this.hash + '/tags/update', this.contactTags)
                        .then(response => {
                            this.getExistingTags()
                        })
            },

            navigateTo(tag) {
                window.location.href = "/people?tag1=" + tag.name_slug
            },

            handleClickOutside(evt) {
                if (!this.$el.contains(evt.target)) {
                    this.isOpen = false;
                    this.arrowCounter = -1;
                }
            }
        }
    }
</script>
