<style lang="scss" scoped>
.reminders {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <layout title="Dashboard">
    <!-- main content -->
    <section class="w-100 pa3 ph5-ns">
      <div class="db cf mw9 center">

        <!-- left column -->
        <div class="fl w-50-l w-100 pr2-l pr0 mb3 mb0-l">

          <!-- upcoming reminders -->
          <reminders
            :reminders="reminders"
          />
        </div>

        <!-- right column -->
        <div class="fl w-50-l w-100 pl2-l pl0">

          <!-- latest activity -->
          <div class="ba mb3 bg-white br2 box bc-gray">
            <h2 class="ma0 bb pa3 fw5 f5 bc-gray c-strong">Latest activity</h2>
            <ul class="ma0 list pl0">
              <li class="bb bc-gray ph3 pv2">
                <span class="db pb1">Added Mali as a new contact</span>
                <span class="f7 dbc-gray">Regis Freyd on Feb 02, 19:23</span>
              </li>
              <li class="ph3 pv2">
                <span class="db pb1">Added Mali as a new contact</span>
                <span class="f7 dbc-gray">Regis Freyd on Feb 02, 19:23</span>
              </li>
            </ul>
          </div>

          <!-- tasks -->
          <div class="ba mb3 bg-white br2 box bc-gray">
            <h2 class="ma0 bb pa3 fw5 f5 bc-gray c-strong">Your tasks</h2>
            <ul class="ma0 list pl0">
              <li class="bb bc-gray pa2">
                <div class="flex items-start">
                  <div class="flex items-center">
                    <input type="checkbox" />
                  </div>
                  <label for="" class="ml2">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue
                  ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas
                  mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu
                  vehicula ultricies a non tortor.</label>
                </div>
              </li>
              <li class="pa2">Reminder 2</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import Reminders from '@/Pages/Dashboard/Partials/Reminders';

export default {
  components: {
    Layout,
    Reminders,
  },

  props: {
    reminders: {
      type: Array,
      default: null,
    },
    tasks: {
      type: Array,
      default: null,
    },
  },
};
</script>
