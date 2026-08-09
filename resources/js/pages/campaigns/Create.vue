<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import FormStepper from '@/components/FormStepper.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import SmsPhonePreview from '@/components/SmsPhonePreview.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormWizard } from '@/composables/useFormWizard';
import type { WizardStep } from '@/composables/useFormWizard';
import { estimate, index, store } from '@/routes/campaigns';
import { download as downloadTemplate } from '@/routes/campaigns/templates';

const props = defineProps<{
    senderIds: { id: number; value: string }[];
    maxMessageLength: number;
    warnThreshold: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: 'New', href: '#' },
        ],
    },
});

const steps: WizardStep[] = [
    {
        id: 'type',
        title: 'Campaign type',
        description: 'Tell us what kind of send this is.',
        fields: ['name', 'campaign_type'],
    },
    {
        id: 'recipients',
        title: 'Recipients',
        description: 'Upload the contact list we should send to.',
        fields: ['file'],
    },
    {
        id: 'message',
        title: 'Message',
        description: 'Pick a sender ID and write the SMS.',
        fields: ['sender_id_id', 'message_body'],
    },
    {
        id: 'schedule',
        title: 'Timing & review',
        description: 'Choose when it should go out, then check the details.',
        fields: ['requested_send_at', 'hard_deadline_at'],
    },
];

const formEl = ref<HTMLElement | null>(null);
const { currentIndex, isActive, isFirst, isLast, goTo, next, back } =
    useFormWizard(steps, formEl);

const campaignName = ref('');
const messageBody = ref('');
const campaignType = ref<'bulk' | 'personalised_bulk'>('bulk');
const senderIdId = ref('');
const requestedSendAt = ref('');
const hardDeadlineAt = ref('');
const selectedFileName = ref('');
const estimateResult = ref<{
    pages: number;
    character_count: number;
    exceeds_621_warning: boolean;
    formatted_cost: string;
    requires_manual_cost_review: boolean;
} | null>(null);

const remaining = computed(
    () => props.maxMessageLength - messageBody.value.length,
);

const campaignTypeLabel = computed(() =>
    campaignType.value === 'personalised_bulk' ? 'Personalised bulk' : 'Bulk',
);

const senderIdOptions = computed(() =>
    props.senderIds.map((sender) => ({
        value: String(sender.id),
        label: sender.value,
    })),
);

const selectedSenderName = computed(
    () =>
        props.senderIds.find(
            (sender) => String(sender.id) === String(senderIdId.value),
        )?.value ?? 'Not selected',
);

const templateHref = computed(() =>
    downloadTemplate.url(
        campaignType.value === 'personalised_bulk'
            ? 'personalised-bulk'
            : 'bulk',
    ),
);

let estimateTimer: ReturnType<typeof setTimeout> | null = null;

watch(messageBody, () => {
    if (estimateTimer) {
        clearTimeout(estimateTimer);
    }

    estimateTimer = setTimeout(async () => {
        if (!messageBody.value) {
            estimateResult.value = null;

            return;
        }

        const response = await fetch(estimate.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((row) => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] ?? '',
                ),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                message_body: messageBody.value,
                billable_recipients: 0,
                encoding: 'text',
            }),
        });

        if (response.ok) {
            estimateResult.value = await response.json();
        }
    }, 300);
});

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    selectedFileName.value = input.files?.[0]?.name ?? '';
}

function onEnter(event: KeyboardEvent): void {
    if (isLast.value || (event.target as HTMLElement).tagName === 'TEXTAREA') {
        return;
    }

    // Enter should advance the wizard rather than submit a half-filled form.
    event.preventDefault();
    next();
}
</script>

<template>
    <Head title="New campaign" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <Heading
            title="New campaign request"
            description="Four short steps: campaign type, recipients, message, then timing and review."
        />

        <Form
            v-bind="store.form()"
            enctype="multipart/form-data"
            class="space-y-6"
            v-slot="{ errors, processing }"
            @keydown.enter="onEnter"
        >
            <FormStepper
                :steps="steps"
                :current-index="currentIndex"
                @select="goTo"
            />

            <div
                v-if="Object.keys(errors).length"
                class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-800 dark:text-red-200"
                role="alert"
            >
                Please fix the highlighted fields before saving.
            </div>

            <div ref="formEl" class="space-y-6">
                <!-- Step 1 — campaign type -->
                <section
                    v-show="isActive('type')"
                    data-step="type"
                    class="space-y-6"
                    aria-labelledby="step-type-heading"
                >
                    <h2
                        id="step-type-heading"
                        data-step-heading
                        tabindex="-1"
                        class="text-base font-medium"
                    >
                        What kind of campaign is this?
                    </h2>

                    <div class="grid gap-2">
                        <Label for="name">Campaign name</Label>
                        <Input
                            id="name"
                            name="name"
                            v-model="campaignName"
                            placeholder="August promo"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <fieldset class="grid gap-3">
                        <legend class="text-sm font-medium">
                            Campaign type
                        </legend>
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card p-4 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-accent/40"
                        >
                            <input
                                v-model="campaignType"
                                type="radio"
                                name="campaign_type"
                                value="bulk"
                                class="mt-1"
                                :required="isActive('type')"
                            />
                            <span class="space-y-1">
                                <span class="block font-medium">Bulk</span>
                                <span
                                    class="block text-sm text-muted-foreground"
                                >
                                    Same message to every contact. Your file
                                    only needs phone numbers.
                                </span>
                            </span>
                        </label>
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card p-4 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-accent/40"
                        >
                            <input
                                v-model="campaignType"
                                type="radio"
                                name="campaign_type"
                                value="personalised_bulk"
                                class="mt-1"
                            />
                            <span class="space-y-1">
                                <span class="block font-medium"
                                    >Personalised bulk</span
                                >
                                <span
                                    class="block text-sm text-muted-foreground"
                                >
                                    Message can include
                                    <code class="rounded bg-muted px-1"
                                        >[Name]</code
                                    >
                                    style placeholders. Extra columns in your
                                    file fill those values per contact.
                                </span>
                            </span>
                        </label>
                        <InputError :message="errors.campaign_type" />
                    </fieldset>
                </section>

                <!-- Step 2 — recipients -->
                <section
                    v-show="isActive('recipients')"
                    data-step="recipients"
                    class="space-y-4 rounded-xl border border-border bg-card p-4"
                    aria-labelledby="step-recipients-heading"
                >
                    <div>
                        <h2
                            id="step-recipients-heading"
                            data-step-heading
                            tabindex="-1"
                            class="text-base font-medium"
                        >
                            Recipient list
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Required. Download the template for your campaign
                            type, fill in contacts, then upload the file here.
                        </p>
                    </div>

                    <p class="text-sm">
                        <a
                            :href="templateHref"
                            class="font-medium text-primary underline underline-offset-4"
                        >
                            Download
                            {{
                                campaignType === 'personalised_bulk'
                                    ? 'personalised bulk'
                                    : 'bulk'
                            }}
                            CSV template
                        </a>
                    </p>

                    <div class="grid gap-2">
                        <Label for="file">Upload CSV or XLSX</Label>
                        <Input
                            id="file"
                            type="file"
                            name="file"
                            accept=".csv,.txt,.xlsx"
                            :required="isActive('recipients')"
                            @change="onFileChange"
                        />
                        <p
                            v-if="selectedFileName"
                            class="text-xs text-muted-foreground"
                        >
                            Selected: {{ selectedFileName }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Prefer a
                            <code class="rounded bg-muted px-1">phone</code>
                            column. Save Excel columns as Text to avoid number
                            corruption.
                        </p>
                        <InputError :message="errors.file" />
                    </div>
                </section>

                <!-- Step 3 — message -->
                <section
                    v-show="isActive('message')"
                    data-step="message"
                    class="space-y-6"
                    aria-labelledby="step-message-heading"
                >
                    <h2
                        id="step-message-heading"
                        data-step-heading
                        tabindex="-1"
                        class="text-base font-medium"
                    >
                        Sender and message
                    </h2>

                    <div
                        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,18rem)] lg:items-start"
                    >
                        <div class="space-y-6">
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
                                    :disabled="senderIds.length === 0"
                                />
                                <InputError :message="errors.sender_id_id" />
                                <p
                                    v-if="senderIds.length === 0"
                                    class="text-xs text-muted-foreground"
                                >
                                    No approved sender IDs yet — register one
                                    first.
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="message_body">Message</Label>
                                <textarea
                                    id="message_body"
                                    name="message_body"
                                    v-model="messageBody"
                                    rows="6"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    :maxlength="maxMessageLength"
                                    :required="isActive('message')"
                                    :placeholder="
                                        campaignType === 'personalised_bulk'
                                            ? 'Hello [Name], your code is [CODE].'
                                            : 'Write the SMS every recipient will get.'
                                    "
                                />
                                <div
                                    class="flex flex-wrap justify-between gap-2 text-xs text-muted-foreground"
                                >
                                    <span>{{ remaining }} characters left</span>
                                    <span v-if="estimateResult">
                                        {{ estimateResult.character_count }}
                                        chars · {{ estimateResult.pages }}
                                        {{
                                            estimateResult.pages === 1
                                                ? 'page'
                                                : 'pages'
                                        }}
                                    </span>
                                </div>
                                <p
                                    v-if="
                                        campaignType === 'personalised_bulk' &&
                                        !messageBody.includes('[')
                                    "
                                    class="text-xs text-muted-foreground"
                                >
                                    Tip: add placeholders like
                                    <code class="rounded bg-muted px-1"
                                        >[Name]</code
                                    >
                                    matching columns in your CSV.
                                </p>
                                <p
                                    v-if="estimateResult?.exceeds_621_warning"
                                    class="text-sm text-amber-700 dark:text-amber-300"
                                >
                                    Messages over {{ warnThreshold }} characters
                                    sit in a range where gateway behaviour can
                                    disagree — consider shortening.
                                </p>
                                <InputError :message="errors.message_body" />
                            </div>
                        </div>

                        <SmsPhonePreview
                            class="lg:sticky lg:top-6"
                            :sender="selectedSenderName"
                            :message="messageBody"
                            :max-length="maxMessageLength"
                        />
                    </div>
                </section>

                <!-- Step 4 — timing and review -->
                <section
                    v-show="isActive('schedule')"
                    data-step="schedule"
                    class="space-y-6"
                    aria-labelledby="step-schedule-heading"
                >
                    <h2
                        id="step-schedule-heading"
                        data-step-heading
                        tabindex="-1"
                        class="text-base font-medium"
                    >
                        Timing and review
                    </h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="requested_send_at"
                                >Requested send time</Label
                            >
                            <Input
                                id="requested_send_at"
                                name="requested_send_at"
                                v-model="requestedSendAt"
                                type="datetime-local"
                            />
                            <InputError :message="errors.requested_send_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="hard_deadline_at">Hard deadline</Label>
                            <Input
                                id="hard_deadline_at"
                                name="hard_deadline_at"
                                v-model="hardDeadlineAt"
                                type="datetime-local"
                            />
                            <InputError :message="errors.hard_deadline_at" />
                        </div>
                    </div>

                    <div class="rounded-xl border border-border bg-card p-4">
                        <h3 class="text-sm font-medium">Check before saving</h3>
                        <dl class="mt-3 space-y-3 text-sm">
                            <div
                                class="flex flex-wrap items-start justify-between gap-2"
                            >
                                <dt class="text-muted-foreground">
                                    Campaign type
                                </dt>
                                <dd class="flex items-center gap-3">
                                    <span class="font-medium">{{
                                        campaignTypeLabel
                                    }}</span>
                                    <button
                                        type="button"
                                        class="text-xs font-medium text-primary underline underline-offset-4"
                                        @click="goTo(0)"
                                    >
                                        Edit
                                    </button>
                                </dd>
                            </div>
                            <div
                                class="flex flex-wrap items-start justify-between gap-2"
                            >
                                <dt class="text-muted-foreground">
                                    Recipient list
                                </dt>
                                <dd class="flex items-center gap-3">
                                    <span
                                        class="font-medium"
                                        :class="
                                            selectedFileName
                                                ? ''
                                                : 'text-amber-700 dark:text-amber-300'
                                        "
                                    >
                                        {{ selectedFileName || 'No file yet' }}
                                    </span>
                                    <button
                                        type="button"
                                        class="text-xs font-medium text-primary underline underline-offset-4"
                                        @click="goTo(1)"
                                    >
                                        Edit
                                    </button>
                                </dd>
                            </div>
                            <div
                                class="flex flex-wrap items-start justify-between gap-2"
                            >
                                <dt class="text-muted-foreground">Sender ID</dt>
                                <dd class="flex items-center gap-3">
                                    <span class="font-medium">{{
                                        selectedSenderName
                                    }}</span>
                                    <button
                                        type="button"
                                        class="text-xs font-medium text-primary underline underline-offset-4"
                                        @click="goTo(2)"
                                    >
                                        Edit
                                    </button>
                                </dd>
                            </div>
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">Message</dt>
                                <dd
                                    class="rounded-lg bg-muted/60 p-3 text-sm break-words whitespace-pre-wrap"
                                >
                                    {{ messageBody || 'Nothing written yet.' }}
                                </dd>
                                <p
                                    v-if="estimateResult"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ estimateResult.character_count }}
                                    characters ·
                                    {{ estimateResult.pages }}
                                    {{
                                        estimateResult.pages === 1
                                            ? 'page'
                                            : 'pages'
                                    }}
                                    per recipient
                                </p>
                            </div>
                        </dl>
                    </div>
                </section>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    v-if="!isFirst"
                    type="button"
                    variant="outline"
                    @click="back"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Back
                </Button>

                <Button v-if="!isLast" type="button" @click="next">
                    Continue
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Button>

                <Button v-else type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Save draft &amp; validate list
                </Button>
            </div>
        </Form>
    </div>
</template>
