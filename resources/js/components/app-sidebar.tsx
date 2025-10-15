import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, FileCog, FileText, PieChart, Settings } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Catalogos',
        icon: FileCog,
        permission: 'catalogos.index',
        items: [
            {
                title: 'Tipos de Empresa',
                href: '#',
                permission: 'tipos-empresa.index',
            },
            {
                title: 'Empresas',
                href: '#',
                permission: 'empresas.index',
            },
            {
                title: 'Ratios Generales',
                href: '#',
                permission: 'ratios-generales.index',
            },
        ],
    },
    {
        title: 'Estados Financieros',
        href: '#',
        icon: PieChart,
        permission: 'estados-financieros.index',
    },
    {
        title: 'Proyecciones',
        href: '#',
        icon: FileText,
        permission: 'proyecciones.index',
    },
    {
        title: 'Análisis Proforma',
        href: '#',
        icon: FileText,
        permission: 'analisis-proforma.index',
    },
    {
        title: 'Informes',
        icon: FileText,
        permission: 'informes.index',
        items: [
            {
                title: 'Análisis Vertical',
                href: '#',
                permission: 'analisis-vertical.index',
            },
            {
                title: 'Análisis Horizontal',
                href: '#',
                permission: 'analisis-horizontal.index',
            },
            {
                title: 'Análisis de Ratios',
                href: '#',
                permission: 'ratios-financieros.index',
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
    },
];

function filterNavItems(items: NavItem[], permissions: string[]): NavItem[] {
    return items
        .map((item) => {
            if (item.items) {
                item.items = filterNavItems(item.items, permissions);
            }
            return item;
        })
        .filter((item) => {
            if (!item.permission) {
                return true;
            }
            if (item.items && item.items.length > 0) {
                return true;
            }
            return permissions.includes(item.permission);
        });
}

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const filteredMainNavItems = filterNavItems(mainNavItems, auth.permissions);

    return (
        <Sidebar collapsible='icon' variant='inset'>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size='lg' asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={filteredMainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className='mt-auto' />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
