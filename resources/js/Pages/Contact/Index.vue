<style lang="scss" scoped>
.contacts {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <layout title="Contacts">
    <!-- main content -->
    <section class="w-100 pa3 ph5-ns">
      <div class="db cf mw9 center">

        <!-- left column -->
        <div class="fl w-80-l w-100 pr2-l pa0">

          <!-- filters -->
          <div class="flex items-center justify-between mb3">
            <div class="">
              You manage {{ count }} contacts (and {{ numberOfArchivedContacts }} archived contacts)
            </div>
            <div class="dib mt0">
              Sort by
              <select name="" id="">
                <option value="">Sort</option>
              </select>
            </div>
          </div>

          <!-- viewed by tags -->
          <div class="flex items-center justify-between mb3">
            <div class="">
              145 contacts have the tag called crazy
            </div>
            <div class="dib mt0">
              <a href="">Show all the contacts</a>
            </div>
          </div>

          <!-- table of contacts -->
          <ul class="ba ma0 list pl0 mb4 contacts">
            <li class="bb pa2 flex items-center" v-for="contact in contacts" :key="contact.id">
              <img :src="contact.avatar" class="br-100 ba h2 w2 dib mr2" alt="avatar">
              <div>
                <inertia-link :href="contact.url" class="mb1 db">{{ contact.name }}</inertia-link>
                <div class="db f7">
                  <!-- tags -->
                  <ul class="dib ma0 list pl0">
                    <li class="di mr1" v-for="tag in contact.tags" :key="tag.id">
                      <inertia-link href="">{{ tag.name }}</inertia-link>
                    </li>
                  </ul>

                  <!-- groups -->
                  <span>Part of:</span>
                  <ul class="di ma0 list pl0">
                    <li class="di"><a href="">Group 1</a></li>
                    <li class="di"><a href="">Group 2</a></li>
                  </ul>
                </div>
              </div>
            </li>
          </ul>

          <!-- pagination -->
          <template v-if="paginator.hasMorePages">
            <div class="db tc">
              <div class="dib overflow-hidden ba br2 b--light-silver">
                <nav class="cf" data-name="pagination-numbers-bordered">
                  <inertia-link class="fl dib link dim black pv2 ph2 br b--light-silver" :href="paginator.previousPageUrl" v-if="paginator.previousPageUrl" title="Previous">&larr; Previous</inertia-link>
                  <inertia-link class="fr dib link dim black pv2 ph2" :href="paginator.nextPageUrl" v-if="paginator.nextPageUrl" title="Next">Next &rarr;</inertia-link>

                  <div class="overflow-hidden center dt tc">
                    <span class="dtc link dim white bg-blue pv2 ph2 br b--light-silver">Page {{ paginator.currentPage }}</span>
                  </div>
                </nav>
              </div>
            </div>
          </template>
        </div>

        <!-- right column -->
        <div class="fl w-20-l w-100 pl2-l pa0">
          <inertia-link :href="urls.cta" class="w-100 db mb4 tc ba pa2">{{ $t('people.people_list_blank_cta') }}</inertia-link>

          <!-- list of tags -->
          <list-tags
            :tags="tags"
            :number-of-archived-contacts="numberOfArchivedContacts"
          />
        </div>

      </div>
    </section>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import ListTags from '@/Pages/Contact/Partials/ListTags';

export default {
  components: {
    Layout,
    ListTags,
  },

  props: {
    count: {
      type: Number,
      default: 0,
    },
    contacts: {
      type: Array,
      default: null,
    },
    tags: {
      type: Array,
      default: null,
    },
    urls: {
      type: Object,
      default: null,
    },
    numberOfArchivedContacts: {
      type: Number,
      default: 0,
    },
    paginator: {
      type: Object,
      default: null,
    }
  },
};
</script>
