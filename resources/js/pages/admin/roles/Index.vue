<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import ListPagination from '@/components/ListPagination.vue';
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
import { index as usersIndex } from '@/routes/admin/users';
import type { Paginated } from '@/types';

type PermissionOption = {
    value: string;
    label: string;
    description: string;
};

type PermissionGroup = {
    group: string;
    permissions: PermissionOption[];
};

type RoleRow = {
    id: number;
    name: string;
    label: string;
    description: string;
    permissions: string[];
    permission_labels: string[];
    users_count: number;
    is_system: boolean;
};

const props = defineProps<{
    roles: Paginated<RoleRow>;
    permissionCatalog: PermissionGroup[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'User management', href: usersIndex() },
            { title: 'Roles', href: rolesIndex() },
        ],
    },
});

const createOpen = ref(false);
const editId = ref<number | null>(null);
const viewId = ref<number | null>(null);
const createPermissions = ref<string[]>(['admin.access']);
const editPermissions = ref<string[]>([]);

const editing = computed(
    () => props.roles.data.find((role) => role.id === editId.value) ?? null,
);

const viewing = computed(
    () => props.roles.data.find((role) => role.id === viewId.value) ?? null,
);

watch(editing, (role) => {
    editPermissions.value = role ? [...role.permissions] : [];
});

function openCreate() {
    createPermissions.value = ['admin.access'];
    createOpen.value = true;
}

function onCreateSuccess() {
    createOpen.value = false;
    createPermissions.value = ['admin.access'];
}

function startView(role: RoleRow) {
    viewId.value = role.id;
}

function startEdit(role: RoleRow) {
    editId.value = role.id;
}

function cancelEdit() {
    editId.value = null;
}

function onEditSuccess() {
    editId.value = null;
}

function togglePermission(target: 'create' | 'edit', value: string) {
    const list = target === 'create' ? createPermissions : editPermissions;

    if (list.value.includes(value)) {
        list.value = list.value.filter((item) => item !== value);
    } else {
        list.value = [...list.value, value];
    }
}

async function destroyRole(role: RoleRow) {
    if (role.is_system) {
        await alertDialog({
            title: 'Cannot delete system role',
            description: 'Built-in system roles cannot be deleted.',
        });

        return;
    }

    if (role.users_count > 0) {
        await alertDialog({
            title: 'Role still in use',
            description: 'Remove all users from this role before deleting it.',
        });

        return;
    }

    const confirmed = await confirmDialog({
        title: `Delete role “${role.label}”?`,
        description: 'This cannot be undone.',
        confirmLabel: 'Delete role',
        cancelLabel: 'Keep role',
        variant: 'destructive',
    });

    if (!confirmed) {
        return;
    }

    router.delete(RoleController.destroy.url(role.id));
}
</script>

<template>
    <Head title="Role management" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Role management"
                description="Assign access permissions to roles. System roles cannot be renamed or deleted, but their permissions can be updated."
            />
            <Button type="button" @click="openCreate">Create role</Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button as-child size="sm" variant="outline">
                <Link :href="usersIndex()">Users</Link>
            </Button>
            <Button as-child size="sm" variant="default">
                <Link :href="rolesIndex()">Roles</Link>
            </Button>
        </div>

        <Dialog v-model:open="createOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Create role</DialogTitle>
                    <DialogDescription>
                        Choose a slug and the access this role should grant.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-bind="RoleController.store.form()"
                    class="grid gap-4"
                    v-slot="{ errors, processing }"
                    :reset-on-success="['name']"
                    @success="onCreateSuccess"
                >
                    <div class="grid gap-2">
                        <Label for="role_name">Role slug</Label>
                        <Input
                            id="role_name"
                            name="name"
                            placeholder="ops-lead"
                            required
                            autocomplete="off"
                        />
                        <p class="text-xs text-muted-foreground">
                            Lowercase letters, numbers, and hyphens only.
                        </p>
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-3">
                        <Label>Access permissions</Label>
                        <input
                            v-for="permission in createPermissions"
                            :key="permission"
                            type="hidden"
                            name="permissions[]"
                            :value="permission"
                        />
                        <div
                            v-for="group in permissionCatalog"
                            :key="group.group"
                            class="rounded-lg border p-3"
                        >
                            <h3 class="mb-2 text-sm font-medium">
                                {{ group.group }}
                            </h3>
                            <div class="grid gap-2">
                                <label
                                    v-for="permission in group.permissions"
                                    :key="permission.value"
                                    class="flex cursor-pointer items-start gap-3 rounded-md p-2 hover:bg-muted/50"
                                >
                                    <input
                                        type="checkbox"
                                        class="mt-1"
                                        :checked="
                                            createPermissions.includes(
                                                permission.value,
                                            )
                                        "
                                        @change="
                                            togglePermission(
                                                'create',
                                                permission.value,
                                            )
                                        "
                                    />
                                    <span>
                                        <span class="block text-sm font-medium">
                                            {{ permission.label }}
                                        </span>
                                        <span
                                            class="block text-xs text-muted-foreground"
                                        >
                                            {{ permission.description }}
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <InputError :message="errors.permissions" />
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
                            Create role
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="editId !== null"
            @update:open="(v) => !v && cancelEdit()"
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit {{ editing?.label }}</DialogTitle>
                    <DialogDescription>
                        <template v-if="editing?.is_system">
                            Update the access this system role grants. The slug
                            cannot be changed.
                        </template>
                        <template v-else>
                            Rename this role and choose which access it grants.
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-if="editing"
                    v-bind="RoleController.update.form(editing.id)"
                    class="grid gap-4"
                    v-slot="{ errors, processing }"
                    @success="onEditSuccess"
                >
                    <div v-if="!editing.is_system" class="grid gap-2">
                        <Label for="edit_role_name">Role slug</Label>
                        <Input
                            id="edit_role_name"
                            name="name"
                            :default-value="editing.name"
                            required
                            autocomplete="off"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        Slug:
                        <span class="font-mono text-foreground">{{
                            editing.name
                        }}</span>
                    </div>

                    <div class="grid gap-3">
                        <Label>Access permissions</Label>
                        <input
                            v-for="permission in editPermissions"
                            :key="permission"
                            type="hidden"
                            name="permissions[]"
                            :value="permission"
                        />
                        <div
                            v-for="group in permissionCatalog"
                            :key="group.group"
                            class="rounded-lg border p-3"
                        >
                            <h3 class="mb-2 text-sm font-medium">
                                {{ group.group }}
                            </h3>
                            <div class="grid gap-2">
                                <label
                                    v-for="permission in group.permissions"
                                    :key="permission.value"
                                    class="flex cursor-pointer items-start gap-3 rounded-md p-2 hover:bg-muted/50"
                                >
                                    <input
                                        type="checkbox"
                                        class="mt-1"
                                        :checked="
                                            editPermissions.includes(
                                                permission.value,
                                            )
                                        "
                                        @change="
                                            togglePermission(
                                                'edit',
                                                permission.value,
                                            )
                                        "
                                    />
                                    <span>
                                        <span class="block text-sm font-medium">
                                            {{ permission.label }}
                                        </span>
                                        <span
                                            class="block text-xs text-muted-foreground"
                                        >
                                            {{ permission.description }}
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <InputError :message="errors.permissions" />
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

        <Dialog
            :open="viewId !== null"
            @update:open="(v) => !v && (viewId = null)"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ viewing?.label }}</DialogTitle>
                    <DialogDescription>
                        {{ viewing?.description }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="viewing" class="space-y-4">
                    <div
                        class="flex flex-wrap gap-3 text-sm text-muted-foreground"
                    >
                        <span class="font-mono text-xs">{{
                            viewing.name
                        }}</span>
                        <span>·</span>
                        <span
                            >{{ viewing.users_count }}
                            {{
                                viewing.users_count === 1 ? 'user' : 'users'
                            }}</span
                        >
                        <span v-if="viewing.is_system">· System role</span>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-medium">
                            Assigned access
                        </h3>
                        <ul
                            v-if="viewing.permission_labels.length"
                            class="list-disc space-y-1.5 pl-5 text-sm text-muted-foreground"
                        >
                            <li
                                v-for="label in viewing.permission_labels"
                                :key="label"
                            >
                                {{ label }}
                            </li>
                        </ul>
                        <p v-else class="text-sm text-muted-foreground">
                            No permissions assigned yet.
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="viewId = null"
                    >
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Access</th>
                        <th class="px-4 py-3 font-medium">Users</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="role in roles.data"
                        :key="role.id"
                        class="border-b align-top last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ role.label }}</div>
                            <div
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ role.name }}
                            </div>
                            <span
                                v-if="role.is_system"
                                class="mt-1 inline-block text-xs text-primary"
                            >
                                System
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="max-w-md text-xs text-muted-foreground">
                                {{
                                    role.permission_labels.length
                                        ? role.permission_labels.join(', ')
                                        : 'No permissions'
                                }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ role.users_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="startView(role)"
                                >
                                    View
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="startEdit(role)"
                                >
                                    Edit
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    :disabled="
                                        role.is_system || role.users_count > 0
                                    "
                                    @click="destroyRole(role)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="roles.data.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No roles found. Run the role seeder to create system
                            roles.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="roles" />
    </div>
</template>
