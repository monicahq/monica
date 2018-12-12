<template>
    <div v-if="item.id > 0" class="header-search-result" :data-contact="item.id" :data-name="item.name">
        <img :src="item.information.avatar.avatar_url" class="avatar" v-if="item.information.avatar.has_avatar">
        <div v-else class="avatar avatar-initials"
             :style="'background-color: '+item.information.avatar.default_avatar_color">
            {{ item.initials }}
        </div>
        <a :href="'/people/'+item.hash_id">{{ item.complete_name }}<span /></a>
    </div>
    <div v-else class="header-search-result">
        <div class="avatar avatar-initials" style="background-color: #fdb660;">+</div>
        <a href="/people/add">Add new person<span /></a>
    </div>
</template>

<script>
export default {
  props: {
    item: { required: true }
  }
}
</script>