import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';

export function LoadingOverlay() {
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        const removeStartListener = router.on('start', (event: any) => {
            // Ignore prefetch requests (like hovering over links)
            if (event.detail?.visit?.prefetch) {
                return;
            }

            // Ignore when progress is explicitly hidden (e.g. real-time updates, polling)
            if (event.detail?.visit?.showProgress === false) {
                return;
            }

            setIsLoading(true);
        });

        const removeFinishListener = router.on('finish', (event: any) => {
            if (event.detail?.visit?.prefetch) {
                return;
            }

            if (event.detail?.visit?.showProgress === false) {
                return;
            }

            setIsLoading(false);
        });

        return () => {
            removeStartListener();
            removeFinishListener();
        };
    }, []);

    if (!isLoading) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-background/50 backdrop-blur-sm">
            <Loader2 className="h-10 w-10 animate-spin text-primary" />
        </div>
    );
}
