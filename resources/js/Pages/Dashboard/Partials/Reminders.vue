<style lang="scss" scoped>
.reminders {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <div class="ba">
    <h2 class="ma0 bb pa2 fw4 f5">Upcoming reminders</h2>
    <ul class="ma0 list pl0 reminders">
      <li class="bb pa2 flex items-center" v-for="reminder in reminders" :key="reminder.id">
        <span class="mr3 tc">
          {{ reminder.month }}
          <span class="db">{{ reminder.day }}</span>
        </span>
        <img :src="reminder.contact.avatar" class="br-100 ba h2 w2 dib mr2" alt="avatar">
        <div>
          {{ reminder.title }}
          <span class="db f6"><inertia-link :href="reminder.contact.url">{{ reminder.contact.name }}</inertia-link></span>
        </div>
      </li>
    </ul>
  </div>
</template>

<script>

export default {
  props: {
    reminders: {
      type: Array,
      default: null,
    },
  },
};
</script>
