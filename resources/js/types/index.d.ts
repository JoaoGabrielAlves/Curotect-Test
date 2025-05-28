import type { PageProps } from '@inertiajs/core';
import Echo from 'laravel-echo';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

// Auth types
export interface Auth {
    user: User;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

// Post types
export interface Post {
    id: number;
    title: string;
    content: string;
    status: 'draft' | 'published' | 'archived';
    category: string | null;
    views_count: number;
    user_id: number;
    published_at: string | null;
    created_at: string;
    updated_at: string;
    user?: User;
    comments_count?: number;
    etag?: string;
}

export interface CreatePostData {
    title: string;
    content: string;
    category?: string;
    status: 'draft' | 'published' | 'archived';
    published_at?: string;
}

export interface UpdatePostData extends CreatePostData {
    etag: string;
}

// Pagination types
export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
    path: string;
}

export interface PaginationLinks {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedPosts {
    data: Post[];
    links: PaginationLinks;
    meta: PaginationMeta;
}

// Filter types
export interface PostFilters {
    search: string;
    category: string;
    status: string;
    sort: string;
    direction: string;
    per_page: number;
}

// Comment types
export interface Comment {
    id: number;
    content: string;
    status: 'pending' | 'approved' | 'rejected';
    post_id: number;
    user_id: number;
    parent_id: number | null;
    created_at: string;
    updated_at: string;
    user?: User;
    replies?: Comment[];
}

// Real-time update types
export interface WelcomeStats {
    totalPosts: number;
    totalUsers: number;
    totalViews: number;
}

export interface DashboardStats {
    totalUsers: number;
    totalPosts: number;
    totalViews: number;
    postsThisMonth: number;
}

export interface UserStats {
    totalPosts: number;
    publishedPosts: number;
    draftPosts: number;
    totalViews: number;
    totalComments: number;
}

export interface RecentPost {
    id: number;
    title: string;
    content?: string;
    status: string;
    category?: string;
    views_count: number;
    comments_count?: number;
    created_at: string;
    user: {
        id: number;
        name: string;
    };
}

export interface PostEventData {
    id: number;
    title: string;
    status: string;
    category?: string;
    views_count: number;
    created_at: string;
    updated_at?: string;
    changes?: {
        status?: {
            old: string;
            new: string;
        };
    };
    user: {
        id: number;
        name: string;
    };
}

export interface PostDeletedEventData {
    id: number;
    title: string;
    status: string;
    user_id: number;
}

export interface InitializeStatsData {
    welcomeStats?: WelcomeStats;
    dashboardStats?: DashboardStats;
    userStats?: UserStats;
    recentPosts?: RecentPost[];
}

// Navigation types
export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

// API types
export interface ApiError {
    message: string;
    status: number;
    errors?: Record<string, string[]>;
}

// Shared page props
export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

// Global declarations
declare global {
    interface Window {
        Echo?: Echo;
    }
}

// Re-exports for convenience
export type { BreadcrumbItem as BreadcrumbItemType };
