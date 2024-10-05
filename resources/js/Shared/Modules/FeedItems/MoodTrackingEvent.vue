<template>
  <div class="rounded-lg border border-gray-300 dark:border-gray-700">
    <!-- contact information -->
    <div
      v-if="!contactViewMode"
      class="flex items-center border-b border-gray-300 px-3 py-2 text-sm dark:border-gray-700">
      <avatar
        :data="data.contact.avatar"
        :class="'relative me-2 h-5 w-5 rounded-full border border-gray-200 dark:border-gray-800'" />

      <div class="flex flex-col">
        <InertiaLink :href="data.contact.url" class="text-gray-800 hover:underline dark:text-gray-200">{{
          data.contact.name
        }}</InertiaLink>
      </div>
    </div>

    <div class="px-3 py-2">
      <!-- the mood tracking event still exists in the system -->
      <div v-if="data.mood_tracking_event.object">
        <div class="mb-2 flex">
          <div class="me-2">
            {{ data.mood_tracking_event.description }}
          </div>

          <!-- slept for -->
          <div v-if="data.mood_tracking_event.object.number_of_hours_slept" class="flex items-center">
            <MoonIcon />
            <span>
              {{
                $tChoice(
                  'Slept :count hour|Slept :count hours',
                  data.mood_tracking_event.object.number_of_hours_slept,
                  { count: data.mood_tracking_event.object.number_of_hours_slept },
                )
              }}
            </span>
          </div>
        </div>

        <!-- date -->
        <div class="flex items-center">
          <CalendarIcon />
          {{ data.mood_tracking_event.object.rated_at }}
        </div>
      </div>

      <!-- the mood tracking event was deleted -->
      <span v-else class="mb-2 me-2">
        <span>{{ data.mood_tracking_event.description }}</span>
      </span>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Shared/Avatar.vue';
import MoonIcon from '@/Shared/Icons/MoonIcon.vue';
import CalendarIcon from '@/Shared/Icons/CalendarIcon.vue';

export default {
  components: {
    InertiaLink: Link,
    Avatar,
    MoonIcon,
    CalendarIcon,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
    contactViewMode: {
      type: Boolean,
      default: true,
    },
  },
};
</script>
