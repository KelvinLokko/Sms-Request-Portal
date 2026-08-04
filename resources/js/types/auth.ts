export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AuthCompany = {
    id: number;
    name: string;
    status: string;
    status_label: string;
};

export type Auth = {
    user: User;
    roles: string[];
    permissions: string[];
    isPlatformStaff: boolean;
    company: AuthCompany | null;
    companyRole: string | null;
};
