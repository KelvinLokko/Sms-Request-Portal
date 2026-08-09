<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CampaignController from '@/actions/App/Http/Controllers/CampaignController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import SmsPhonePreview from '@/components/SmsPhonePreview.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useRecipientListPolling } from '@/composables/useRecipientListPolling';
import { index, show } from '@/routes/campaigns';
import { download as downloadTemplateRoute } from '@/routes/campaigns/templates';

type Campaign = {
    id: number;
    reference: string;
    name: string | null;
    status_label: string;
    message_body: string | null;
    is_personalised: boolean;
    sender_id_id: number | null;
    sender_id: string | null;
    requested_send_at: string | null;
    hard_deadline_at: string | null;
    recipient_list: {
        status: string;
        status_label: string;
        original_filename: string;
        valid_count: number;
        invalid_count: number;
        duplicate_count: number;
        billable_count: number;
        error_message: string | null;
    } | null;
};

const props = defineProps<{
    campaign: Campaign;
    senderIds: { id: number; value: string }[];
    maxMessageLength: number;
    warnThreshold: number;
    can: {
        upload: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});

const page = usePage();
const flashSuccess = computed(
    () =>
        (page.props.flash as { success?: string | null } | undefined)?.success,
);

const senderIdId = ref(String(props.campaign.sender_id_id ?? ''));
const messageBody = ref(props.campaign.message_body ?? '');

const senderIdOptions = computed(() =>
    props.senderIds.map((sender) => ({
        value: String(sender.id),
        label: sender.value,
    })),
);

const selectedSenderName = computed(
    () =>
        senderIdOptions.value.find(
            (option) => option.value === senderIdId.value,
        )?.label ?? '',
);

const remaining = computed(
    () => props.maxMessageLength - messageBody.value.length,
);

const isPersonalised = ref(props.campaign.is_personalised);

const templateType = computed(() =>
    isPersonalised.value ? 'personalised-bulk' : 'bulk',
);

const listStatus = computed(
    () => props.campaign.recipient_list?.status ?? null,
);

const listPending = computed(() => {
    const status = listStatus.value;

    return status === 'pending' || status === 'processing';
});

useRecipientListPolling(listStatus);

function toLocalInput(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function downloadTemplateUrl(type: 'bulk' | 'personalised-bulk'): string {
    return downloadTemplateRoute.url(type);
}
</script>

<template>
    <Head :title="`Edit ${campaign.reference}`" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Heading
                    title="Edit campaign"
                    :description="campaign.name || campaign.reference"
                />
            </div>
            <Button variant="outline" as-child>
                <Link :href="show(campaign.id)">Done</Link>
            </Button>
        </div>

        <div
            v-if="flashSuccess"
            class="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm"
            role="status"
        >
            {{ flashSuccess }}
        </div>

        <Form
            v-bind="CampaignController.update.form(campaign.id)"
            class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start"
            v-slot="{ errors, processing }"
        >
            <div class="min-w-0 space-y-6">
                <div class="grid gap-2">
                    <Label for="name">Campaign name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="campaign.name ?? ''"
                        placeholder="campaign name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="sender_id_id">Sender ID</Label>
                    <SearchableSelect
                        id="sender_id_id"
                        name="sender_id_id"
                        v-model="senderIdId"
                        :options="senderIdOptions"
                        placeholder="Select approved sender ID"
                        search-placeholder="Search sender IDs…"
                        empty-text="No matching sender ID."
                    />
                    <InputError :message="errors.sender_id_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="message_body">Message</Label>
                    <textarea
                        id="message_body"
                        name="message_body"
                        v-model="messageBody"
                        rows="8"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        :maxlength="maxMessageLength"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ remaining }} characters left
                    </p>
                    <InputError :message="errors.message_body" />
                </div>

                <fieldset class="grid gap-3 border-t pt-6">
                    <legend class="sr-only">Campaign type</legend>
                    <p class="text-sm font-medium">Campaign type</p>
                    <label class="flex items-start gap-3 text-sm">
                        <input
                            type="radio"
                            name="campaign_type"
                            value="bulk"
                            class="mt-1"
                            :checked="!campaign.is_personalised"
                            required
                            @change="isPersonalised = false"
                        />
                        <span>
                            <span class="font-medium">Bulk</span>
                            — same message for every contact
                        </span>
                    </label>
                    <label class="flex items-start gap-3 text-sm">
                        <input
                            type="radio"
                            name="campaign_type"
                            value="personalised_bulk"
                            class="mt-1"
                            :checked="campaign.is_personalised"
                            @change="isPersonalised = true"
                        />
                        <span>
                            <span class="font-medium">Personalised bulk</span>
                            — uses
                            <code class="rounded bg-muted px-1">[HEADER]</code>
                            placeholders
                        </span>
                    </label>
                    <InputError :message="errors.campaign_type" />
                </fieldset>

                <div class="grid gap-4 border-t pt-6 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="requested_send_at">
                            Requested send time
                        </Label>
                        <Input
                            id="requested_send_at"
                            name="requested_send_at"
                            type="datetime-local"
                            :default-value="
                                toLocalInput(campaign.requested_send_at)
                            "
                        />
                        <InputError :message="errors.requested_send_at" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="hard_deadline_at">Hard deadline</Label>
                        <Input
                            id="hard_deadline_at"
                            name="hard_deadline_at"
                            type="datetime-local"
                            :default-value="
                                toLocalInput(campaign.hard_deadline_at)
                            "
                        />
                        <InputError :message="errors.hard_deadline_at" />
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Save changes
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="show(campaign.id)">Cancel</Link>
                    </Button>
                </div>
            </div>

            <SmsPhonePreview
                class="lg:sticky lg:top-6"
                :sender="selectedSenderName || campaign.sender_id"
                :message="messageBody"
                :max-length="maxMessageLength"
            />
        </Form>

        <section class="space-y-4 border-t pt-8">
            <div>
                <h2 class="text-sm font-medium">Recipient list</h2>
                <p
                    v-if="campaign.recipient_list"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ campaign.recipient_list.original_filename }} ·
                    {{ campaign.recipient_list.status_label }} ·
                    {{ campaign.recipient_list.billable_count }} billable
                </p>
                <p
                    v-if="listPending"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    Validation is running. Counts update automatically when it
                    finishes.
                </p>
                <p
                    v-else-if="!campaign.recipient_list"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    No recipient list uploaded yet.
                </p>
            </div>

            <Form
                v-if="can.upload"
                v-bind="CampaignController.uploadRecipients.form(campaign.id)"
                enctype="multipart/form-data"
                class="max-w-xl space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="file">Upload CSV or XLSX</Label>
                    <Input
                        id="file"
                        type="file"
                        name="file"
                        accept=".csv,.txt,.xlsx"
                        required
                    />
                    <p class="text-xs text-muted-foreground">
                        Prefer a
                        <code class="rounded bg-muted px-1">phone</code>
                        column and save Excel columns as Text.
                        <a
                            :href="downloadTemplateUrl(templateType)"
                            class="underline underline-offset-4"
                        >
                            Download template
                        </a>
                    </p>
                    <InputError :message="errors.file" />
                </div>
                <Button type="submit" variant="outline" :disabled="processing">
                    <Spinner v-if="processing" />
                    Upload &amp; validate
                </Button>
            </Form>
        </section>
    </div>
</template>
