<template>
    <div class="flex items-center gap-2">
        <Button v-if="canManagePost" variant="ghost" size="sm" @click.stop="editPost" class="h-8 w-8 p-0">
            <Icon name="edit" class="h-4 w-4" />
            <span class="sr-only">Edit post</span>
        </Button>

        <Button
            v-if="canManagePost"
            variant="ghost"
            size="sm"
            @click.stop="confirmDelete"
            class="text-destructive hover:text-destructive hover:bg-destructive/10 h-8 w-8 p-0"
        >
            <Icon name="trash-2" class="h-4 w-4 text-red-600" />
            <span class="sr-only">Delete post</span>
        </Button>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteDialog">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Delete Post</DialogTitle>
                <DialogDescription> Are you sure you want to delete "{{ post.title }}"? This action cannot be undone. </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                <Button variant="destructive" @click="deletePost" :disabled="isDeleting">
                    <Icon v-if="isDeleting" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                    Delete Post
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { Post } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    post: Post;
}

const props = defineProps<Props>();

const page = usePage();
const showDeleteDialog = ref(false);
const isDeleting = ref(false);

// Check if current user can manage this post
const canManagePost = computed(() => {
    const currentUser = page.props.auth?.user;
    return currentUser && currentUser.id === props.post.user_id;
});

const editPost = () => {
    router.get(`/posts/${props.post.id}/edit`);
};

const confirmDelete = () => {
    showDeleteDialog.value = true;
};

const deletePost = () => {
    isDeleting.value = true;

    router.delete(`/posts/${props.post.id}`, {
        onSuccess: () => {
            showDeleteDialog.value = false;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>
