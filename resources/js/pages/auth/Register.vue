<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { ref } from 'vue';
import FormStepper from '@/components/FormStepper.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useFormWizard } from '@/composables/useFormWizard';
import type { WizardStep } from '@/composables/useFormWizard';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Register your company to submit SMS campaign requests',
    },
});

const steps: WizardStep[] = [
    {
        id: 'you',
        title: 'About you',
        description: 'How we address you and where we send updates.',
        fields: ['name', 'email'],
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

function onEnter(event: KeyboardEvent): void {
    if (isLast.value) {
        return;
    }

    // Enter should advance the wizard rather than submit a half-filled form.
    event.preventDefault();
    next();
}
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
        @keydown.enter="onEnter"
    >
        <FormStepper
            :steps="steps"
            :current-index="currentIndex"
            @select="goTo"
        />

        <div ref="formEl" class="grid gap-6">
            <section
                v-show="isActive('you')"
                data-step="you"
                class="grid gap-6"
                aria-labelledby="step-you-heading"
            >
                <h2
                    id="step-you-heading"
                    data-step-heading
                    tabindex="-1"
                    class="sr-only"
                >
                    About you
                </h2>

                <div class="grid gap-2">
                    <Label for="name">Your name</Label>
                    <Input
                        id="name"
                        type="text"
                        :required="isActive('you')"
                        autofocus
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Work email</Label>
                    <Input
                        id="email"
                        type="email"
                        :required="isActive('you')"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
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
                        :required="isActive('company')"
                        autocomplete="organization"
                        name="company_name"
                        placeholder="Acme Ltd"
                    />
                    <InputError :message="errors.company_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_phone">Company phone (optional)</Label>
                    <Input
                        id="company_phone"
                        type="tel"
                        autocomplete="tel"
                        name="company_phone"
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
                        :required="isActive('security')"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        :required="isActive('security')"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>
            </section>

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

                <Button
                    v-if="!isLast"
                    type="button"
                    class="flex-1"
                    @click="next"
                >
                    Continue
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Button>

                <Button
                    v-else
                    type="submit"
                    class="flex-1"
                    :disabled="processing"
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
