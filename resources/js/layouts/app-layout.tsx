import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { Toaster } from '@/components/ui/sonner';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode, useEffect } from 'react'; // Import useEffect
import { usePage } from '@inertiajs/react'; // Import usePage
import { toast } from 'sonner'; // Import toast

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => {
    const { flash } = usePage().props as any; // Access flash messages

    useEffect(() => {
        // Add a check for flash existence
        if (flash) {
            if (flash.success) {
                toast.success(flash.success);
            }
            if (flash.error) {
                toast.error(flash.error);
            }
        }
    }, [flash]); // Re-run effect when flash messages change

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            {children}
            <Toaster position="top-right" />
        </AppLayoutTemplate>
    );
};
