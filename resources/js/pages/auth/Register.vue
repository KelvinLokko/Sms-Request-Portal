<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import FormStepper from '@/components/FormStepper.vue';
import HoneypotInput from '@/components/HoneypotInput.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import TurnstileWidget from '@/components/TurnstileWidget.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormWizard } from '@/composables/useFormWizard';
import type { WizardStep } from '@/composables/useFormWizard';
import { login } from '@/routes';
import { store } from '@/routes/register';

const props = defineProps<{
    passwordRules: string;
    turnstileSiteKey: string | null;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Verify your email, then register your company',
    },
});

const steps: WizardStep[] = [
    {
        id: 'contact',
        title: 'Your email',
        description: 'We send a one-time code before signup continues.',
        fields: ['name', 'email', 'cf-turnstile-response'],
    },
    {
        id: 'verify',
        title: 'Verify',
        description: 'Enter the 6-digit code from your inbox.',
        fields: ['code', 'registration_token'],
    },
    {
        id: 'company',
        title: 'Your company',
        description: 'The organisation campaigns will be billed to.',
        fields: ['company_name', 'company_phone'],
    },
    {
        id: 'security',
        title: 'Password',
        description: 'Secure the account before we create it.',
        fields: ['password', 'password_confirmation'],
    },
];

const formEl = ref<HTMLElement | null>(null);
const { currentIndex, isActive, isFirst, isLast, goTo, next, back } =
    useFormWizard(steps, formEl);

const name = ref('');
const email = ref('');
const otpCode = ref('');
const registrationToken = ref('');
const emailVerified = ref(false);
const turnstileToken = ref('');
const turnstileResetKey = ref(0);
const otpSending = ref(false);
const otpVerifying = ref(false);
const otpError = ref('');
const otpStatus = ref('');
const canResendAt = ref(0);
const now = ref(Date.now());

const resendSecondsLeft = computed(() =>
    Math.max(0, Math.ceil((canResendAt.value - now.value) / 1000)),
);
const canResend = computed(() => resendSecondsLeft.value <= 0);
const resendLabel = computed(() =>
    canResend.value
        ? 'Resend code'
        : `Resend in ${resendSecondsLeft.value}s`,
);

let resendTimer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    resendTimer = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (resendTimer) {
        clearInterval(resendTimer);
    }
});

function csrfToken(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

async function postJson(
    url: string,
    body: Record<string, unknown>,
): Promise<Record<string, unknown>> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    const payload = (await response.json().catch(() => ({}))) as Record<
        string,
        unknown
    >;

    if (!response.ok) {
        const errors = payload.errors as Record<string, string[]> | undefined;
        const first =
            errors?.email?.[0] ??
            errors?.code?.[0] ??
            errors?.name?.[0] ??
            errors?.['cf-turnstile-response']?.[0] ??
            (payload.message as string | undefined) ??
            'Something went wrong. Please try again.';

        throw new Error(first);
    }

    return payload;
}

async function sendOtp(): Promise<void> {
    otpError.value = '';
    otpStatus.value = '';

    if (!name.value.trim() || !email.value.trim()) {
        otpError.value = 'Enter your name and work email first.';

        return;
    }

    if (props.turnstileSiteKey && !turnstileToken.value) {
        otpError.value = 'Complete the security check first.';

        return;
    }

    otpSending.value = true;

    try {
        const payload = await postJson('/register/otp/send', {
            name: name.value.trim(),
            email: email.value.trim(),
            website: '',
            'cf-turnstile-response': turnstileToken.value || null,
        });

        otpStatus.value =
            (payload.message as string) ??
            'We sent a 6-digit code to your email.';
        canResendAt.value = Date.now() + 60_000;
        emailVerified.value = false;
        registrationToken.value = '';
        otpCode.value = '';
        goTo(1);
    } catch (error) {
        otpError.value =
            error instanceof Error ? error.message : 'Unable to send code.';
        turnstileResetKey.value += 1;
        turnstileToken.value = '';
    } finally {
        otpSending.value = false;
    }
}

async function verifyOtp(): Promise<void> {
    otpError.value = '';
    otpStatus.value = '';

    if (otpCode.value.length !== 6) {
        otpError.value = 'Enter the 6-digit code from your email.';

        return;
    }

    otpVerifying.value = true;

    try {
        const payload = await postJson('/register/otp/verify', {
            email: email.value.trim(),
            code: otpCode.value,
            website: '',
        });

        registrationToken.value = String(payload.registration_token ?? '');
        emailVerified.value = true;
        otpStatus.value =
            (payload.message as string) ?? 'Email verified. Continue.';
        goTo(2);
    } catch (error) {
        otpError.value =
            error instanceof Error ? error.message : 'Unable to verify code.';
        registrationToken.value = '';
        emailVerified.value = false;
    } finally {
        otpVerifying.value = false;
    }
}

function continueFromContact(): void {
    if (emailVerified.value && registrationToken.value) {
        next();

        return;
    }

    void sendOtp();
}

function continueFromVerify(): void {
    if (emailVerified.value && registrationToken.value) {
        next();

        return;
    }

    void verifyOtp();
}

function onEnter(event: KeyboardEvent): void {
    if (isLast.value) {
        return;
    }

    event.preventDefault();

    if (isActive('contact')) {
        continueFromContact();

        return;
    }

    if (isActive('verify')) {
        continueFromVerify();

        return;
    }

    next();
}

function onWizardBack(): void {
    back();
}
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="relative flex flex-col gap-6"
        @keydown.enter="onEnter"
    >
        <HoneypotInput />

        <input
            type="hidden"
            name="registration_token"
            :value="registrationToken"
        />
        <input type="hidden" name="name" :value="name" />
        <input type="hidden" name="email" :value="email" />

        <FormStepper
            :steps="steps"
            :current-index="currentIndex"
            @select="goTo"
        />

        <div
            v-if="otpError || errors.registration_token || errors.email"
            class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-800 dark:text-red-200"
            role="alert"
        >
            {{
                otpError ||
                errors.registration_token ||
                errors.email ||
                errors['cf-turnstile-response']
            }}
        </div>

        <p
            v-if="otpStatus && !otpError"
            class="rounded-xl border border-primary/20 bg-primary/10 px-4 py-3 text-sm text-foreground"
            role="status"
        >
            {{ otpStatus }}
        </p>

        <div ref="formEl" class="grid gap-6">
            <section
                v-show="isActive('contact')"
                data-step="contact"
                class="grid gap-6"
                aria-labelledby="step-contact-heading"
            >
                <h2
                    id="step-contact-heading"
                    data-step-heading
                    tabindex="-1"
                    class="sr-only"
                >
                    Your email
                </h2>

                <div class="grid gap-2">
                    <Label for="name">Your name</Label>
                    <Input
                        id="name"
                        type="text"
                        v-model="name"
                        :required="isActive('contact')"
                        autofocus
                        autocomplete="name"
                        placeholder="Full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Work email</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="email"
                        :required="isActive('contact')"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                    <p class="text-xs text-muted-foreground">
                        We’ll email a one-time code before you can continue.
                    </p>
                </div>

                <TurnstileWidget
                    :site-key="turnstileSiteKey"
                    :include-hidden-input="false"
                    :reset-key="turnstileResetKey"
                    @token="turnstileToken = $event"
                    @expired="turnstileToken = ''"
                    @error="turnstileToken = ''"
                />
                <InputError :message="errors['cf-turnstile-response']" />
            </section>

            <section
                v-show="isActive('verify')"
                data-step="verify"
                class="grid gap-6"
                aria-labelledby="step-verify-heading"
            >
                <h2
                    id="step-verify-heading"
                    data-step-heading
                    tabindex="-1"
                    class="text-base font-medium"
                >
                    Enter the code sent to {{ email || 'your email' }}
                </h2>

                <div class="grid gap-3">
                    <Label for="otp-code">Verification code</Label>
                    <InputOTP
                        id="otp-code"
                        v-model="otpCode"
                        :maxlength="6"
                        :disabled="emailVerified"
                    >
                        <InputOTPGroup class="gap-2.5 sm:gap-3">
                            <InputOTPSlot
                                v-for="index in 6"
                                :key="index"
                                :index="index - 1"
                            />
                        </InputOTPGroup>
                    </InputOTP>
                    <InputError :message="errors.code" />
                </div>

                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <button
                        type="button"
                        class="font-medium text-foreground underline underline-offset-4 disabled:cursor-not-allowed disabled:no-underline disabled:opacity-60"
                        :disabled="!canResend || otpSending || emailVerified"
                        @click="sendOtp"
                    >
                        {{ resendLabel }}
                    </button>
                    <button
                        type="button"
                        class="text-muted-foreground underline underline-offset-4"
                        @click="goTo(0)"
                    >
                        Change email
                    </button>
                </div>
            </section>

            <section
                v-show="isActive('company')"
                data-step="company"
                class="grid gap-6"
                aria-labelledby="step-company-heading"
            >
                <h2
                    id="step-company-heading"
                    data-step-heading
                    tabindex="-1"
                    class="sr-only"
                >
                    Your company
                </h2>

                <div class="grid gap-2">
                    <Label for="company_name">Company name</Label>
                    <Input
                        id="company_name"
                        type="text"
                        name="company_name"
                        :required="isActive('company')"
                        autocomplete="organization"
                        placeholder="Acme Ltd"
                    />
                    <InputError :message="errors.company_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_phone">Company phone (optional)</Label>
                    <Input
                        id="company_phone"
                        type="tel"
                        name="company_phone"
                        autocomplete="tel"
                        placeholder="0XXXXXXXXX"
                    />
                    <InputError :message="errors.company_phone" />
                </div>
            </section>

            <section
                v-show="isActive('security')"
                data-step="security"
                class="grid gap-6"
                aria-labelledby="step-security-heading"
            >
                <h2
                    id="step-security-heading"
                    data-step-heading
                    tabindex="-1"
                    class="sr-only"
                >
                    Password
                </h2>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        :required="isActive('security')"
                        autocomplete="new-password"
                        placeholder="Password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        :required="isActive('security')"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <TurnstileWidget
                    v-if="isActive('security') && turnstileSiteKey"
                    :site-key="turnstileSiteKey"
                    :reset-key="`final-${turnstileResetKey}`"
                />
            </section>

            <div class="flex items-center gap-3">
                <Button
                    v-if="!isFirst"
                    type="button"
                    variant="outline"
                    @click="onWizardBack"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Back
                </Button>

                <Button
                    v-if="isActive('contact')"
                    type="button"
                    class="flex-1"
                    :disabled="otpSending"
                    @click="continueFromContact"
                >
                    <Spinner v-if="otpSending" />
                    Send verification code
                    <ArrowRight
                        v-if="!otpSending"
                        class="size-4"
                        aria-hidden="true"
                    />
                </Button>

                <Button
                    v-else-if="isActive('verify')"
                    type="button"
                    class="flex-1"
                    :disabled="otpVerifying"
                    @click="continueFromVerify"
                >
                    <Spinner v-if="otpVerifying" />
                    Verify &amp; continue
                    <ArrowRight
                        v-if="!otpVerifying"
                        class="size-4"
                        aria-hidden="true"
                    />
                </Button>

                <Button
                    v-else-if="!isLast"
                    type="button"
                    class="flex-1"
                    :disabled="!emailVerified"
                    @click="next"
                >
                    Continue
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Button>

                <Button
                    v-else
                    type="submit"
                    class="flex-1"
                    :disabled="processing || !emailVerified"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
            </div>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink :href="login()" class="underline underline-offset-4">
                Log in
            </TextLink>
        </div>
    </Form>
</template>
