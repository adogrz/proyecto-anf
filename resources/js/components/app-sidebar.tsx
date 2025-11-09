"use client";

import * as React from "react";
import { useEffect, useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import { route } from "ziggy-js";

import { NavMain } from "@/components/nav-main";
import { NavUser } from "@/components/nav-user";
import AppLogo from "./app-logo";

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from "@/components/ui/sidebar";

import {
    LayoutGrid,
    FileCog,
    FileText,
    PieChart,
    ShieldCheck,
    FileUp,
} from "lucide-react";

import { type NavItem, type SharedData } from "@/types";

export function AppSidebar(props: React.ComponentProps<typeof Sidebar>) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const currentUrl = page.url;

    const [openGroups, setOpenGroups] = useState<Record<string, boolean>>({});

    // Cargar grupos desde localStorage
    useEffect(() => {
        try {
            const saved = localStorage.getItem("sidebar-open-groups");
            if (saved) setOpenGroups(JSON.parse(saved));
        } catch { }
    }, []);

    // Guardar grupos abiertos
    const toggleGroup = (key: string) => {
        setOpenGroups((prev) => {
            const updated = { ...prev, [key]: !prev[key] };
            localStorage.setItem("sidebar-open-groups", JSON.stringify(updated));
            return updated;
        });
    };

    // Determinar si un enlace está activo
    const getHrefString = (href: string | { url?: string } | undefined) =>
        typeof href === "string" ? href : href?.url;

    const isItemActive = (href: string | { url?: string } | undefined) => {
        const hrefStr = getHrefString(href);
        if (!hrefStr) return false;
        try {
            const linkUrl = new URL(hrefStr, window.location.origin);
            const linkPath = linkUrl.pathname;
            return (
                linkPath === currentUrl ||
                currentUrl.startsWith(linkPath) ||
                (linkPath === "/dashboard" && currentUrl === "/")
            );
        } catch {
            return false;
        }
    };

    // =======================
    // ESTRUCTURA PRINCIPAL
    // =======================
    const navStructure: NavItem[] = [
        {
            title: "Dashboard",
            href: route("dashboard"),
            icon: LayoutGrid,
        },
        {
            title: "Importación",
            href: route("importacion.wizard"),
            icon: FileUp,
            permission: "estados-financieros.create",
        },
        {
            title: "Administración",
            icon: ShieldCheck,
            permission: "administracion.index",
            items: [
                {
                    title: "Sectores",
                    href: route("sectores.index"),
                    permission: "sectores.index",
                },

                {
                    title: "Cuentas Base",
                    href: route("cuentas-base.index"),
                    permission: "cuentas-base.index",
                },
                {
                    title: "Plantillas de Catálogo",
                    href: route("plantillas-catalogo.index"),
                    permission: "plantillas-catalogo.index",
                },
            ],
        },
        {
            title: "Empresas",
            icon: FileCog,
            permission: "empresas.index",
            items: [
                {
                    title: "Listado de Empresas",
                    href: route("empresas.index"),
                    permission: "empresas.index",
                },
                {
                    title: "Catálogos Contables",
                    href: route("empresas.index"),
                    permission: "catalogos.index",
                },
                {
                    title: "Estados Financieros",
                    href: route("empresas.index"),
                    permission: "estados-financieros.index",
                },
                {
                    title: "Proyecciones",
                    href: route("empresas.index"),
                    permission: "proyecciones.index",
                },
            ],
        },
        {
            title: "Análisis",
            icon: PieChart,
            permission: "informes.index",
            items: [
                {
                    title: "Ratios Comparativos",
                    href: route("analisis.ratios", { empresa: 1, anio: new Date().getFullYear() }),
                    permission: "informes.index",
                },
                {
                    title: "Historial de Cuenta",
                    href: route("analisis.historial-cuenta", { empresa: 1 }),
                    permission: "informes.index",
                },
            ],
        },
    ];


    // =======================
    // FILTRO POR PERMISOS
    // =======================
    function filterNavItems(items: NavItem[], permissions: string[]): NavItem[] {
        return items
            .map((item) => {
                if (item.items) {
                    const sub = filterNavItems(item.items, permissions);
                    return { ...item, items: sub };
                }
                return item;
            })
            .filter((item) => {
                if (!item.permission) return true;
                if (item.items && item.items.length > 0) return true;
                return permissions.includes(item.permission);
            });
    }

    // =======================
    // MAPEO FINAL DE ITEMS
    // =======================
    const mainNavItems = navStructure.map((item, idx) => {
        const key = item.title || `group-${idx}`;
        const isGroup = !!item.items;
        const groupIsOpen =
            isGroup &&
            (openGroups[key] ||
                item.items!.some((sub) => isItemActive(sub.href)));

        return {
            ...item,
            isActive: !isGroup && isItemActive(item.href),
            isOpen: groupIsOpen,
            toggleKey: key,
            items: item.items?.map((subItem) => ({
                ...subItem,
                isActive: isItemActive(subItem.href),
            })),
        };
    });

    const filteredMainNavItems = filterNavItems(mainNavItems, auth.permissions);

    // =======================
    // RENDER
    // =======================
    return (
        <Sidebar collapsible="icon" {...props}>
            {/* === HEADER === */}
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route("dashboard")} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            {/* === CONTENIDO === */}
            <SidebarContent>
                <NavMain
                    items={filteredMainNavItems}
                    onGroupToggle={toggleGroup}
                    isGroupOpen={(key: string) => !!openGroups[key]}
                />
            </SidebarContent>

            {/* === FOOTER === */}
            <SidebarFooter>
                <NavUser />
            </SidebarFooter>

            {/* === RAIL === */}
            <SidebarRail />
        </Sidebar>
    );
}
