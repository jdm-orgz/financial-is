import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationProps {
    links: PaginationLink[];
    className?: string;
}

export function Pagination({ links, className }: PaginationProps) {
    if (!links || links.length <= 3) return null; // Only Previous, 1, Next

    return (
        <div className={cn('flex items-center justify-end space-x-2 py-4', className)}>
            {links.map((link, i) => {
                const isPrevious = link.label.includes('Previous');
                const isNext = link.label.includes('Next');

                if (link.url === null) {
                    return (
                        <Button
                            key={i}
                            variant="outline"
                            size={isPrevious || isNext ? "default" : "icon"}
                            disabled
                            className="opacity-50"
                        >
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Button>
                    );
                }

                return (
                    <Button
                        key={i}
                        variant={link.active ? 'default' : 'outline'}
                        size={isPrevious || isNext ? "default" : "icon"}
                        asChild
                    >
                        <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                    </Button>
                );
            })}
        </div>
    );
}
