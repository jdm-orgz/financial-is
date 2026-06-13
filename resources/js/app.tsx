import { createInertiaApp, router } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { LoadingOverlay } from '@/components/loading-overlay';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => {
        const dynamicAppName = document.querySelector('meta[name="app-name"]')?.getAttribute('content') || appName;
        return title ? `${title} - ${dynamicAppName}` : dynamicAppName;
    },
    layout: (name) => {
        if (name === 'Error') {
            return ({ children }: any) => <>{children}</>;
        }
        
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <LoadingOverlay />
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// Update the document title, meta tag, and favicon automatically when props change
router.on('success', (event) => {
    // 1. App Name updates
    const newName = event.detail.page.props.name as string | undefined;
    if (newName) {
        const meta = document.querySelector('meta[name="app-name"]');
        if (meta && meta.getAttribute('content') !== newName) {
            meta.setAttribute('content', newName);
            // Re-apply the document title if it was constructed using the old name
            if (document.title.includes(' - ')) {
                const parts = document.title.split(' - ');
                parts[parts.length - 1] = newName;
                document.title = parts.join(' - ');
            }
        }
    }

    // 2. Favicon updates
    // Use strictly the 'app_logo' property to know if a logo exists
    if ('app_logo' in event.detail.page.props) {
        const appLogo = event.detail.page.props.app_logo as string | null;
        if (appLogo) {
            document.querySelectorAll('link[rel="icon"], link[rel="apple-touch-icon"]').forEach(link => {
                link.setAttribute('href', appLogo);
            });
        } else {
            document.querySelector('link[rel="icon"][sizes="any"]')?.setAttribute('href', '/favicon.ico');
            document.querySelector('link[rel="icon"][type="image/svg+xml"]')?.setAttribute('href', '/favicon.svg');
            document.querySelector('link[rel="apple-touch-icon"]')?.setAttribute('href', '/apple-touch-icon.png');
        }
    }
});

// This will set light / dark mode on load...
initializeTheme();
