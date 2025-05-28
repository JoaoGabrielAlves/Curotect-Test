<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <MessageSquare class="h-5 w-5" />
                Add a Comment
            </CardTitle>
            <CardDescription>Share your thoughts about this post</CardDescription>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="submitComment" class="space-y-4">
                <div>
                    <Textarea
                        v-model="form.content"
                        placeholder="Write your comment here..."
                        :rows="4"
                        :class="{ 'border-destructive': shouldShowError('content') }"
                        @input="clearFieldError('content')"
                    />
                    <p v-if="shouldShowError('content')" class="text-destructive mt-1 text-sm">
                        {{ errors.content }}
                    </p>
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ form.content.length }}/1000 characters</p>
                    <Button type="submit" :disabled="isSubmitting" class="flex items-center gap-2">
                        <Send class="h-4 w-4" />
                        {{ isSubmitting ? 'Posting...' : 'Post Comment' }}
                    </Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { router } from '@inertiajs/vue3';
import { MessageSquare, Send } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

interface Props {
    postId: number;
    parentId?: number;
}

const props = defineProps<Props>();

const form = reactive({
    content: '',
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});
const hasAttemptedSubmit = ref(false);

const shouldShowError = (field: string) => {
    return hasAttemptedSubmit.value && errors.value[field];
};

const clearFieldError = (field: string) => {
    if (errors.value[field]) {
        errors.value[field] = '';
    }
};

const submitComment = async () => {
    hasAttemptedSubmit.value = true;

    if (!form.content.trim()) {
        errors.value.content = 'Comment content is required';
        return;
    }

    isSubmitting.value = true;
    errors.value = {};

    router.post(
        route('comments.store', props.postId),
        {
            content: form.content,
            parent_id: props.parentId || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                form.content = '';
                hasAttemptedSubmit.value = false;
            },
            onError: (responseErrors) => {
                errors.value = responseErrors;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};
</script>
