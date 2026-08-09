<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { alertDialog, confirmDialog } from '@/composables/useConfirmDialog';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index } from '@/routes/admin/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    type: 'staff' | 'client';
    platform_roles: string[];
    company: { id: number; name: string } | null;
    company_role: string | null;
    companies_count: number;
    created_at: string | null;
    is_self: boolean;
};

const props = defineProps<{
    users: Paginated<UserRow>;
    filters: { type: string | null; q: string | null };
    companies: { id: number; name: string }[];
    platformRoles: { value: string; label: string }[];
    companyRoles: { value: string; label: string }[];
    authUserId: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'User management', href: index() }],
    },
});

const createOpen = ref(false);
const createType = ref<'staff' | 'client'>('staff');
const editId = ref<number | null>(null);

const editing = computed(
    () => props.users.data.find((user) => user.id === editId.value) ?? null,
);

function openCreate() {
    createType.value = 'staff';
    createOpen.value = true;
}

function onCreateSuccess() {
    createOpen.value = false;
    createType.value = 'staff';
}

function filterType(type: string | null) {
    router.get(
        index.url(),
        {
            ...(type ? { type } : {}),
            ...(props.filters.q ? { q: props.filters.q } : {}),
        },
        { preserveState: true },
    );
}

function search(event: Event) {
    const value = (event.target as HTMLInputElement).value.trim();
    router.get(
        index.url(),
        {
            ...(props.filters.type ? { type: props.filters.type } : {}),
            ...(value ? { q: value } : {}),
        },
        { preserveState: true, replace: true },
    );
}

function startEdit(user: UserRow) {
    editId.value = user.id;
}

function cancelEdit() {
    editId.value = null;
}

async function destroyUser(user: UserRow) {
    if (user.is_self) {
        await alertDialog({
            title: 'Cannot delete your account',
            description: 'You cannot delete your own account.',
        });

        return;
    }

    const confirmed = await confirmDialog({
        title: `Delete ${user.name}?`,
        description: 'This cannot be undone.',
        confirmLabel: 'Delete user',
        cancelLabel: 'Keep user',
        variant: 'destructive',
    });

    if (!confirmed) {
        return;
    }

    router.delete(UserController.destroy.url(user.id));
}
</script>

<template>
    <Head title="User management" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="User management"
                description="Create and manage platform staff and client users. You cannot delete your own account."
            />
            <Button type="button" @click="openCreate">Create user</Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button as-child size="sm" variant="default">
                <Link :href="index()">Users</Link>
            </Button>
            <Button as-child size="sm" variant="outline">
                <Link :href="rolesIndex()">Roles</Link>
            </Button>
        </div>

        <Dialog v-model:open="createOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Create user</DialogTitle>
                    <DialogDescription>
                        Add a platform staff member or a client user for a
                        company.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-bind="UserController.store.form()"
                    class="grid gap-4"
                    v-slot="{ errors, processing }"
                    :reset-on-success="[
                        'name',
                        'email',
                        'password',
                        'password_confirmation',
                    ]"
                    @success="onCreateSuccess"
                >
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2">
                            <input
                                v-model="createType"
                                type="radio"
                                name="type"
                                value="staff"
                            />
                            Platform staff
                        </label>
                        <label class="flex items-center gap-2">
                            <input
                                v-model="createType"
                                type="radio"
                                name="type"
                                value="client"
                            />
                            Client user
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="create_name">Name</Label>
                            <Input id="create_name" name="name" required />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create_email">Email</Label>
                            <Input
                                id="create_email"
                                name="email"
                                type="email"
                                required
                            />
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create_password">Password</Label>
                            <Input
                                id="create_password"
                                name="password"
                                type="password"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create_password_confirmation"
                                >Confirm password</Label
                            >
                            <Input
                                id="create_password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                            />
                        </div>
                    </div>

                    <div v-if="createType === 'staff'" class="grid gap-2">
                        <Label for="create_platform_role">Platform role</Label>
                        <select
                            id="create_platform_role"
                            name="platform_role"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                            required
                        >
                            <option value="">Select role</option>
                            <option
                                v-for="role in platformRoles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <InputError :message="errors.platform_role" />
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="create_company_id">Company</Label>
                            <select
                                id="create_company_id"
                                name="company_id"
                                class="h-9 rounded-md border bg-background px-3 text-sm"
                                required
                            >
                                <option value="">Select company</option>
                                <option
                                    v-for="company in companies"
                                    :key="company.id"
                                    :value="company.id"
                                >
                                    {{ company.name }}
                                </option>
                            </select>
                            <InputError :message="errors.company_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create_company_role"
                                >Company role</Label
                            >
                            <select
                                id="create_company_role"
                                name="company_role"
                                class="h-9 rounded-md border bg-background px-3 text-sm"
                                required
                            >
                                <option value="">Select role</option>
                                <option
                                    v-for="role in companyRoles"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </option>
                            </select>
                            <InputError :message="errors.company_role" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            @click="createOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="processing">
                            Create user
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <div class="flex flex-wrap items-center gap-2">
            <Button
                size="sm"
                :variant="!filters.type ? 'default' : 'outline'"
                @click="filterType(null)"
            >
                All
            </Button>
            <Button
                size="sm"
                :variant="filters.type === 'staff' ? 'default' : 'outline'"
                @click="filterType('staff')"
            >
                Staff
            </Button>
            <Button
                size="sm"
                :variant="filters.type === 'client' ? 'default' : 'outline'"
                @click="filterType('client')"
            >
                Clients
            </Button>
            <Input
                class="max-w-xs"
                type="search"
                placeholder="Search name or email"
                :default-value="filters.q ?? ''"
                @change="search"
            />
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">User</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Access</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-b align-top last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ user.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ user.email }}
                            </div>
                            <span
                                v-if="user.is_self"
                                class="mt-1 inline-block text-xs text-primary"
                            >
                                You
                            </span>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ user.type }}</td>
                        <td class="px-4 py-3">
                            <template v-if="user.type === 'staff'">
                                {{ user.platform_roles.join(', ') || '—' }}
                            </template>
                            <template v-else>
                                <div>
                                    {{ user.company?.name ?? 'No company' }}
                                </div>
                                <div
                                    class="text-xs text-muted-foreground capitalize"
                                >
                                    {{ user.company_role ?? '—' }}
                                </div>
                            </template>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="startEdit(user)"
                                >
                                    Edit
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    :disabled="user.is_self"
                                    @click="destroyUser(user)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No users match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="users" />

        <Dialog
            :open="editId !== null"
            @update:open="(v) => !v && cancelEdit()"
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit {{ editing?.name }}</DialogTitle>
                    <DialogDescription>
                        Update profile details and access for this user.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-if="editing"
                    v-bind="UserController.update.form(editing.id)"
                    class="grid gap-4"
                    v-slot="{ errors, processing }"
                    @success="cancelEdit"
                >
                    <input type="hidden" name="type" :value="editing.type" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="edit_name">Name</Label>
                            <Input
                                id="edit_name"
                                name="name"
                                :default-value="editing.name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_email">Email</Label>
                            <Input
                                id="edit_email"
                                name="email"
                                type="email"
                                :default-value="editing.email"
                                required
                            />
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_password"
                                >New password (optional)</Label
                            >
                            <Input
                                id="edit_password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                            />
                            <InputError :message="errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_password_confirmation"
                                >Confirm password</Label
                            >
                            <Input
                                id="edit_password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                            />
                        </div>
                    </div>

                    <div v-if="editing.type === 'staff'" class="grid gap-2">
                        <Label for="edit_platform_role">Platform role</Label>
                        <select
                            id="edit_platform_role"
                            name="platform_role"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                            :value="editing.platform_roles[0] ?? ''"
                            required
                        >
                            <option
                                v-for="role in platformRoles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <InputError :message="errors.platform_role" />
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="edit_company_id">Company</Label>
                            <select
                                id="edit_company_id"
                                name="company_id"
                                class="h-9 rounded-md border bg-background px-3 text-sm"
                                :value="editing.company?.id ?? ''"
                                required
                            >
                                <option
                                    v-for="company in companies"
                                    :key="company.id"
                                    :value="company.id"
                                >
                                    {{ company.name }}
                                </option>
                            </select>
                            <InputError :message="errors.company_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_company_role">Company role</Label>
                            <select
                                id="edit_company_role"
                                name="company_role"
                                class="h-9 rounded-md border bg-background px-3 text-sm"
                                :value="editing.company_role ?? ''"
                                required
                            >
                                <option
                                    v-for="role in companyRoles"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </option>
                            </select>
                            <InputError :message="errors.company_role" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            @click="cancelEdit"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="processing">
                            Save changes
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
