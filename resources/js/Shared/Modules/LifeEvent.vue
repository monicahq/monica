<script setup>
import Loading from '@/Shared/Loading.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import ContactCard from '@/Shared/ContactCard.vue';
import HoverMenu from '@/Shared/HoverMenu.vue';
import CreateLifeEvent from '@/Shared/Modules/CreateLifeEvent.vue';
import { onMounted, ref } from 'vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const createLifeEventModalShown = ref(false);
const loadingData = ref(false);
const paginator = ref([]);
const localTimelines = ref([]);
const showAddLifeEventModalForTimelineEventId = ref(0);

onMounted(() => {
  initialLoad();
});

const initialLoad = () => {
  loadingData.value = true;

  axios
    .get(props.data.url.load)
    .then((response) => {
      loadingData.value = false;
      response.data.data.timeline_events.forEach((entry) => {
        localTimelines.value.push(entry);
      });
      paginator.value = response.data.paginator;
    })
    .catch(() => {});
};

const loadMore = () => {
  axios
    .get(paginator.value.nextPageUrl)
    .then((response) => {
      loadingData.value = false;
      response.data.data.timeline_events.forEach((entry) => {
        localTimelines.value.push(entry);
      });
      paginator.value = response.data.paginator;
    })
    .catch(() => {});
};

const destroy = (timelineEvent) => {
  if (confirm('Are you sure? This will delete the event permanently.')) {
    axios
      .delete(timelineEvent.url.destroy)
      .then(() => {
        var id = localTimelines.value.findIndex((x) => x.id === timelineEvent.id);
        localTimelines.value.splice(id, 1);
      })
      .catch(() => {});
  }
};

const destroyLifeEvent = (timelineEvent, lifeEvent) => {
  if (confirm('Are you sure? This will delete the event permanently.')) {
    axios
      .delete(lifeEvent.url.destroy)
      .then(() => {
        var id = localTimelines.value.findIndex((x) => x.id === timelineEvent.id);
        var lifeEventId = localTimelines.value[id].life_events.findIndex((x) => x.id === lifeEvent.id);
        localTimelines.value[id].life_events.splice(lifeEventId, 1);
      })
      .catch(() => {});
  }
};

const refreshTimelineEvents = (timelineEvent) => {
  localTimelines.value.unshift(timelineEvent);
};

const refreshLifeEvents = (lifeEvent) => {
  var id = localTimelines.value.findIndex((x) => x.id === lifeEvent.timeline_event.id);
  localTimelines.value[id].life_events.unshift(lifeEvent);
};

const showCreateLifeEventModal = () => {
  createLifeEventModalShown.value = true;
};

const toggleTimelineEventVisibility = (timelineEvent) => {
  timelineEvent.collapsed = !timelineEvent.collapsed;

  axios.post(timelineEvent.url.toggle);
};

const toggleLifeEventVisibility = (lifeEvent) => {
  lifeEvent.collapsed = !lifeEvent.collapsed;

  axios.post(lifeEvent.url.toggle);
};
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="icon-sidebar relative inline h-4 w-4">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
          </svg>
        </span>

        <span class="font-semibold"> Life events </span>
      </div>
      <pretty-button
        :text="'Add a life event'"
        :icon="'plus'"
        :classes="'sm:w-fit w-full'"
        @click="showCreateLifeEventModal" />
    </div>

    <div>
      <!-- add a timeline event -->
      <create-life-event
        :data="props.data"
        :layout-data="props.layoutData"
        :open-modal="createLifeEventModalShown"
        :create-timeline-event="true"
        @close-modal="createLifeEventModalShown = false"
        @timeline-event-created="refreshTimelineEvents" />

      <!-- list of timeline events -->
      <div v-if="localTimelines">
        <div v-for="timelineEvent in localTimelines" :key="timelineEvent.id" class="mb-4">
          <!-- timeline event name -->
          <div
            class="mb-2 flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800"
            @click="toggleTimelineEventVisibility(timelineEvent)">
            <!-- timeline date / label / number of events -->
            <div>
              <span class="mr-2 text-gray-500">{{ timelineEvent.happened_at }}</span>

              <span class="ml-3 whitespace-nowrap rounded-lg bg-slate-100 py-0.5 px-2 text-sm text-slate-400">{{
                timelineEvent.life_events.length
              }}</span>
            </div>

            <!-- chevrons and menu -->
            <div class="flex">
              <!-- chevrons -->
              <svg
                v-if="timelineEvent.collapsed"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="mr-2 h-4 w-4 text-gray-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
              </svg>

              <svg
                v-if="!timelineEvent.collapsed"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="mr-2 h-4 w-4 text-gray-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
              </svg>

              <!-- menu -->
              <hover-menu
                :show-edit="true"
                :show-delete="true"
                @edit="showEditNoteModal(note)"
                @delete="destroy(timelineEvent)" />
            </div>
          </div>

          <!-- life events -->
          <div v-if="!timelineEvent.collapsed">
            <div
              v-for="lifeEvent in timelineEvent.life_events"
              :key="lifeEvent.id"
              :class="!lifeEvent.collapsed ? 'border' : ''"
              class="ml-6 mb-2 rounded-lg border-gray-200">
              <!-- name of life event -->
              <div
                :class="lifeEvent.collapsed ? 'rounded-lg border' : ''"
                class="flex cursor-pointer items-center justify-between rounded-t-lg border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
                <!-- title -->
                <div @click="toggleLifeEventVisibility(lifeEvent)" class="flex items-center">
                  <p v-if="lifeEvent.summary" class="mr-4 text-sm font-bold">{{ lifeEvent.summary }}</p>
                  <div>
                    <span class="rounded border bg-white px-2 py-1 font-mono text-sm">{{
                      lifeEvent.life_event_type.category.label
                    }}</span>
                    >
                    <span class="rounded border bg-white px-2 py-1 font-mono text-sm">{{
                      lifeEvent.life_event_type.label
                    }}</span>
                  </div>
                </div>

                <!-- chevrons and menu -->
                <div class="flex">
                  <!-- chevrons -->
                  <svg
                    @click="toggleLifeEventVisibility(lifeEvent)"
                    v-if="lifeEvent.collapsed"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mr-2 h-4 w-4 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                  </svg>

                  <svg
                    @click="toggleLifeEventVisibility(lifeEvent)"
                    v-if="!lifeEvent.collapsed"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mr-2 h-4 w-4 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                  </svg>

                  <!-- menu -->
                  <hover-menu
                    :show-edit="true"
                    :show-delete="true"
                    @edit="showEditNoteModal(note)"
                    @delete="destroyLifeEvent(timelineEvent, lifeEvent)" />
                </div>
              </div>

              <!-- description -->
              <div
                v-if="!lifeEvent.collapsed && lifeEvent.description"
                class="flex items-center border-b border-gray-200 px-3 py-2">
                {{ lifeEvent.description }}
              </div>

              <!-- date of life event | distance -->
              <div v-if="!lifeEvent.collapsed" class="flex items-center border-b border-gray-200 px-3 py-2 text-sm">
                <!-- date -->
                <div class="mr-4 flex items-center">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mr-2 h-4 w-4 text-gray-500">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>

                  {{ lifeEvent.happened_at }}
                </div>

                <!-- distance -->
                <div v-if="lifeEvent.distance" class="flex items-center">
                  <svg
                    class="mr-2 h-6 w-6 text-gray-500"
                    viewBox="0 0 64 64"
                    xmlns="http://www.w3.org/2000/svg"
                    stroke-width="3"
                    stroke="#000000"
                    fill="none">
                    <path
                      d="M17.94,54.81a.1.1,0,0,1-.14,0c-1-1.11-11.69-13.23-11.69-21.26,0-9.94,6.5-12.24,11.76-12.24,4.84,0,11.06,2.6,11.06,12.24C28.93,41.84,18.87,53.72,17.94,54.81Z" />
                    <circle cx="17.52" cy="31.38" r="4.75" />
                    <path
                      d="M49.58,34.77a.11.11,0,0,1-.15,0c-.87-1-9.19-10.45-9.19-16.74,0-7.84,5.12-9.65,9.27-9.65,3.81,0,8.71,2,8.71,9.65C58.22,24.52,50.4,33.81,49.58,34.77Z" />
                    <circle cx="49.23" cy="17.32" r="3.75" />
                    <path d="M17.87,54.89a28.73,28.73,0,0,0,3.9.89" />
                    <path
                      d="M24.68,56.07c2.79.12,5.85-.28,7.9-2.08,5.8-5.09,2.89-11.25,6.75-14.71a16.72,16.72,0,0,1,4.93-3"
                      stroke-dasharray="7.8 2.92" />
                    <path d="M45.63,35.8a23,23,0,0,1,3.88-.95" />
                  </svg>

                  <span>{{ lifeEvent.distance }}</span>
                </div>
              </div>

              <!-- participants -->
              <div v-if="!lifeEvent.collapsed" class="flex p-3 pb-1">
                <div v-for="contact in lifeEvent.participants" :key="contact.id" class="mr-4">
                  <contact-card :contact="contact" :avatarClasses="'h-5 w-5 rounded-full mr-2'" :displayName="true" />
                </div>
              </div>
            </div>

            <!-- add a new life event to the timeline -->
            <div class="ml-6 mb-2">
              <span
                @click="showAddLifeEventModalForTimelineEventId = timelineEvent.id"
                v-if="showAddLifeEventModalForTimelineEventId != timelineEvent.id"
                class="cursor-pointer text-sm text-blue-500 hover:underline">
                Add another life event
              </span>

              <create-life-event
                :data="props.data"
                :layout-data="props.layoutData"
                :open-modal="showAddLifeEventModalForTimelineEventId == timelineEvent.id"
                :create-timeline-event="false"
                :timeline-event="timelineEvent"
                @close-modal="showAddLifeEventModalForTimelineEventId = 0"
                @life-event-created="refreshLifeEvents" />
            </div>
          </div>
        </div>

        <!-- pagination -->
        <div class="text-center" v-if="paginator.hasMorePages">
          <span
            @click="loadMore()"
            class="cursor-pointer rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
            {{ $t('app.view_older') }}
          </span>
        </div>
      </div>

      <!-- loading mode -->
      <div v-if="loadingData" class="mb-5 rounded-lg border border-gray-200 p-20 text-center">
        <loading />
      </div>

      <!-- blank state -->
      <div
        v-if="localTimelines.length == 0"
        class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <img src="/img/contact_blank_life_event.svg" :alt="$t('Life events')" class="mx-auto mt-4 h-20 w-20" />
        <p class="px-5 pb-5 pt-2 text-center">Life events let you document what happened in your life.</p>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}

.icon-search {
  left: 8px;
  top: 8px;
}

.grid-skeleton {
  grid-template-columns: 1fr 2fr;
}

@media (max-width: 480px) {
  .grid-skeleton {
    grid-template-columns: 1fr;
  }
}

.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
