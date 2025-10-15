import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

function NavItem({ item }: { item: NavItem }) {
    const page = usePage();
    const isActive = item.href && page.url.startsWith(typeof item.href === 'string' ? item.href : item.href.url);

    if (item.items && item.items.length > 0) {
        return (
            <SidebarGroup className='px-2 py-0'>
                <SidebarGroupLabel>{item.title}</SidebarGroupLabel>
                <SidebarMenu>
                    {item.items.map((subItem) => (
                        <NavItem key={subItem.title} item={subItem} />
                    ))}
                </SidebarMenu>
            </SidebarGroup>
        );
    }

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={isActive}
                tooltip={{ children: item.title }}
            >
                <Link href={item.href!} prefetch>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

export function NavMain({ items = [] }: { items: NavItem[] }) {
    return (
        <SidebarGroup className='px-2 py-0'>
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <NavItem key={item.title} item={item} />
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
