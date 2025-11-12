<template>
    <button
        @click="$emit('click')"
        :class="[
      'w-full flex items-center gap-3 px-3 py-2 text-xs rounded-xl transition',
      active ? 'bg-white/20 text-white shadow-lg shadow-black/40'
             : 'text-white/80 hover:bg-white/10',
    ]"
    >
        <span class="text-base w-5 text-center">{{ icon }}</span>
        <span v-if="!collapsed" class="truncate text-left">{{ label }}</span>
    </button>
</template>
<script setup>
defineProps({
    icon: String,
    label: String,
    active: Boolean,
    collapsed: Boolean,
});
defineEmits(['click']);
</script>
