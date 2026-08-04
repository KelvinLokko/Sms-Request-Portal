<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index, store } from '@/routes/sender-ids';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sender IDs', href: index() },
            { title: 'Register', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Register sender ID" />

    <div class="mx-auto flex max-w-lg flex-col gap-6 p-4">
        <Heading
            title="Register sender ID"
            description="Letters, numbers and spaces only — maximum 11 characters."
        />

        <Form
            v-bind="store.form()"
            enctype="multipart/form-data"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="value">Sender ID</Label>
                <Input
                    id="value"
                    name="value"
                    required
                    maxlength="11"
                    placeholder="AcmeGH"
                    autocomplete="off"
                />
                <InputError :message="errors.value" />
            </div>

            <div class="grid gap-2">
                <Label for="document">Supporting document (optional)</Label>
                <Input
                    id="document"
                    type="file"
                    name="document"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                />
                <p class="text-xs text-muted-foreground">
                    PDF, Word or image — max 5 MB.
                </p>
                <InputError :message="errors.document" />
            </div>

            <label class="flex items-start gap-2 text-sm">
                <input
                    type="checkbox"
                    name="uses_company_letterhead"
                    value="1"
                    class="mt-1"
                />
                <span>Uses company letterhead already on file</span>
            </label>

            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                Submit for review
            </Button>
        </Form>
    </div>
</template>
