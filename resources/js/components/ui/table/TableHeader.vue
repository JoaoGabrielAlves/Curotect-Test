<template>
  <thead :class="cn('[&_tr]:border-b', $attrs.class)">
    <slot />
  </thead>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils'
</script> 