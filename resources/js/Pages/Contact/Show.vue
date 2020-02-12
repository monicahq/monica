<style lang="scss" scoped>
.contacts {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <layout title="Contact page">
    <!-- breadcrumb -->
    <section class="w-100 ph3 ph5-ns bb">
      <div class="db mw9 center w-100 breadcrumb f6">
        <ul class="list ph0 tl db mh0 mv2">
          <li class="di">
            <inertia-link :href="'/dashboard'">{{ $t('app.breadcrumb_dashboard') }}</inertia-link>
          </li>
          <li class="di">
            <inertia-link :href="'/people'">{{ $t('app.breadcrumb_list_contacts') }}</inertia-link>
          </li>
          <li class="di">
            {{ $t('app.breadcrumb_contact', {name : contact.name}) }}
          </li>
        </ul>
      </div>
    </section>

    <!-- main content -->
    <section class="w-100 pa3 ph5-ns">
      <div class="db cf mw9 center">

        <!-- left column -->
        <div class="fl w-30-l w-100 pr2-l pa0">

          <!-- profile box -->
          <div class="ba mb3">
            <div class="tc bb pa2">
              <img :src="contact.avatar" class="br-100 ba h4 w4 dib mr2 mb3" alt="avatar">
              <h1 class="mv0 fw4 f4">{{ contact.name }} <span v-if="contact.age">({{ contact.age }})</span></h1>
            </div>

            <!-- description -->
            <description
              :description="contact.description"
              :contact-id="contact.id"
            />

            <!-- family -->
            <div class="pa2 bb">
              <p class="mb1 f6 mt0">Family</p>
              <p class="mv0 lh-copy"><a href="">Jessica</a> <span>(32 - wife)</span>, <a href="">Jessica</a> <span>(32 - wife)</span> and <a href="">Jessica</a> <span>(32 - wife)</span></p>
            </div>

            <!-- job -->
            <div class="pa2 bb">
              <p class="mb1 f6 mt0">Job</p>
              <p class="mv0 lh-copy">Software developer at <a href="">Microsoft</a></p>
            </div>

            <!-- address -->
            <div class="pa2">
              <p class="mb1 f6 mt0">Address</p>
              <p class="mv0 lh-copy">234, rue du saint sacré coeur, Montreal</p>
            </div>
          </div>

          <!-- actions -->
          <ul class="ma0 list pl0">
            <li class="mb2 f6"><a href="">Export as vCard</a></li>
            <li class="mb2 f6"><a href="">Archive the contact</a></li>
            <li class="mb2 f6"><a href="">Delete contact</a></li>
          </ul>

        </div>

        <!-- right column -->
        <div class="fl w-70-l w-100 pl2-l pa0">

          <!-- contact / weather -->
          <div class="ba mb3">
            <h2 class="ma0 bb pa2 fw4 f5">Contact information</h2>
            <ul class="ma0 list pl0">
              <li class="pa2">
                <a href="">https://facebook.com/regis</a>
              </li>
              <li class="pa2">
                <a href="">+ 3483 384384 8234</a>
              </li>
            </ul>
          </div>

          <!-- reminders and tasks -->
          <div class="ba mb3">

            <ul class="ma0 list pt3 ph2 bb">
              <li class="di">
                <a href="" class="bt bl br no-underline ph2 pt2 pb2 bg-white bb-0" style="border-bottom: 1px solid #fff">Reminders <span>4</span></a>
              </li>
              <li class="di">
                <a href="" class="bt bl br no-underline ph2 pt2 pb2 dib">Tasks <span>4</span></a>
              </li>
            </ul>

            <!-- content -->
            <ul class="ma0 list pl0">
              <li class="bb pa2 flex items-center">
                <span class="mr3 tc">
                  Feb
                  <span class="db">25</span>
                </span>
                <div>
                  Anniversary of the day you first met
                  <span class="db f6"><a href="">Regis Freyd</a></span>
                </div>
              </li>
              <li class="pa2 flex items-center">
                <span class="mr3 tc">
                  Feb
                  <span class="db">25</span>
                </span>
                <div>
                  Anniversary of the day you first met
                  <span class="db f6"><a href="">Regis Freyd</a></span>
                </div>
              </li>
            </ul>
          </div>

          <div class="ba mb3">

            <!-- menu -->
            <ul class="ma0 list pt3 ph2 bb">
              <li class="di">
                <a href="" class="bt bl br no-underline ph2 pt2 pb2 bg-white bb-0 z-999">History</a>
              </li>
              <li class="di">
                <a href="" class="no-underline ph2 pt2 pb2 dib">Life event <span>4</span></a>
              </li>
              <li class="di">
                <a href="" class="no-underline ph2 pt2 pb2 dib">Notes <span>4</span></a>
              </li>
              <li class="di">
                <a href="" class="no-underline ph2 pt2 pb2 dib">Social</a>
              </li>
              <li class="di">
                <a href="" class="no-underline ph2 pt2 pb2 dib">Photos & documents</a>
              </li>
              <li class="di">
                <a href="" class="no-underline ph2 pt2 pb2 dib">Gifts & debts</a>
              </li>
            </ul>

            <!-- history -->
            <ul class="ma0 list pl0">
              <li class="pa2">
                <div class="mb2">
                  Created the contact
                </div>
                <span class="f6 db">Regis Freyd on Feb 02, 19:23</span>
              </li>
              <li class="pa2">
                <div class="mb2">
                  Created the contact
                </div>
                <span class="f6 db">Regis Freyd on Feb 02, 19:23</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import ListTags from '@/Pages/Contact/Partials/ListTags';
import Description from '@/Pages/Contact/Partials/Description';

export default {
  components: {
    Layout,
    ListTags,
    Description,
  },

  props: {
    contact: {
      type: Object,
      default: null,
    },
    tags: {
      type: Array,
      default: null,
    },
    numberOfArchivedContacts: {
      type: Number,
      default: 0,
    },
    paginator: {
      type: Object,
      default: null,
    },
  },
};
</script>
