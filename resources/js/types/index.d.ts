import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

// Declaración global de Ziggy route
declare global {
    function route(name?: undefined): {
        current: (name?: string) => string | boolean;
    };
    function route<T extends string | number>(
        name: string,
        params?: T | Record<string, T | T[]>,
        absolute?: boolean
    ): string;
}

export interface Auth {
    user: User;
    roles: string[];
    permissions: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    permission?: string;
    items?: NavItem[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Sector {
    id: number;
    nombre: string;
}

export interface PlantillaCatalogo {
    id: number;
    nombre: string;
    cuentasBase?: CuentaBase[];
}

export interface CuentaBase {
    id: number;
    plantilla_catalogo_id: number;
    parent_id: number | null;
    codigo: string;
    nombre: string;
    tipo_cuenta: 'AGRUPACION' | 'DETALLE';
    naturaleza: 'DEUDORA' | 'ACREEDORA';
}

export interface Empresa {
    id: number;
    nombre: string;
    sector_id: number;
    plantilla_catalogo_id: number;
}

export interface CatalogoCuenta {
    id: number;
    empresa_id: number;
    codigo_cuenta: string;
    nombre_cuenta: string;
    cuenta_base_id: number | null;
    cuentaBase?: { nombre: string; };
}
