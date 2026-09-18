import { Head, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDownAZ,
    ArrowDownZA,
    ArrowUpDown,
    CheckSquare2,
    ChevronLeft,
    ChevronRight,
    Download,
    FileVideo,
    Image,
    Loader2,
    Square,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { GalleryFile, GalleryFilters, GalleryGroup } from '@/types';

interface IndexProps {
    groups: GalleryGroup[];
    filters: GalleryFilters;
    total: number;
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
return `${bytes} B`;
}

    if (bytes < 1024 * 1024) {
return `${(bytes / 1024).toFixed(1)} KB`;
}

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function Index({ groups, filters, total }: IndexProps) {
    const [selected, setSelected] = useState<Set<string>>(new Set());
    const [viewerFile, setViewerFile] = useState<GalleryFile | null>(null);
    const [viewerGroup, setViewerGroup] = useState<GalleryGroup | null>(null);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [isDownloading, setIsDownloading] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const videoRef = useRef<HTMLVideoElement>(null);

    // All files flat list for viewer navigation
    const allFiles = groups.flatMap((g) => g.files);

    const toggleSelect = (path: string) => {
        setSelected((prev) => {
            const next = new Set(prev);

            if (next.has(path)) {
                next.delete(path);
            } else {
                next.add(path);
            }

            return next;
        });
    };

    const selectAll = () => {
        if (selected.size === allFiles.length) {
            setSelected(new Set());
        } else {
            setSelected(new Set(allFiles.map((f) => f.path)));
        }
    };

    const handleFilterChange = (key: keyof GalleryFilters, value: string) => {
        router.get(
            window.location.pathname,
            { ...filters, [key]: value },
            { preserveState: true, preserveScroll: true },
        );
    };

    const openViewer = (file: GalleryFile, group: GalleryGroup) => {
        setViewerFile(file);
        setViewerGroup(group);
    };

    const closeViewer = () => {
        setViewerFile(null);
        setViewerGroup(null);
    };

    const navigateViewer = useCallback(
        (direction: 'prev' | 'next') => {
            if (!viewerFile || !viewerGroup) {
return;
}

            const idx = viewerGroup.files.findIndex((f) => f.path === viewerFile.path);
            const newIdx = direction === 'prev' ? idx - 1 : idx + 1;

            if (newIdx >= 0 && newIdx < viewerGroup.files.length) {
                setViewerFile(viewerGroup.files[newIdx]);
            }
        },
        [viewerFile, viewerGroup],
    );

    // Keyboard navigation in viewer
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (!viewerFile) {
return;
}

            if (e.key === 'ArrowLeft') {
navigateViewer('prev');
}

            if (e.key === 'ArrowRight') {
navigateViewer('next');
}

            if (e.key === 'Escape') {
closeViewer();
}
        };
        window.addEventListener('keydown', handler);

        return () => window.removeEventListener('keydown', handler);
    }, [viewerFile, navigateViewer]);

    // Reset video when viewer changes
    useEffect(() => {
        if (viewerFile?.type === 'video' && videoRef.current) {
            videoRef.current.load();
        }
    }, [viewerFile]);

    const handleDownload = async () => {
        if (selected.size === 0) {
return;
}

        setIsDownloading(true);

        try {
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
            const response = await fetch('/gallery/download', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ paths: Array.from(selected) }),
            });

            if (!response.ok) {
throw new Error('Download failed');
}

            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `gallery_${Date.now()}.zip`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch {
            // silently fail - the user can retry
        } finally {
            setIsDownloading(false);
        }
    };

    const handleDelete = () => {
        if (selected.size === 0) {
return;
}

        setIsDeleting(true);
        router.delete('/gallery', {
            data: { paths: Array.from(selected) },
            onSuccess: () => {
                setSelected(new Set());
                setShowDeleteConfirm(false);
            },
            onFinish: () => setIsDeleting(false),
        });
    };

    const viewerIdx = viewerFile && viewerGroup
        ? viewerGroup.files.findIndex((f) => f.path === viewerFile.path)
        : -1;

    return (
        <>
            <Head title="Gallery" />

            {/* Download overlay */}
            {isDownloading && (
                <div className="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-black/60 backdrop-blur-sm">
                    <div className="flex flex-col items-center gap-4 rounded-2xl bg-white px-10 py-8 shadow-2xl dark:bg-zinc-900">
                        <Loader2 className="h-10 w-10 animate-spin text-primary" />
                        <p className="text-sm font-medium text-muted-foreground">
                            Preparing your download…
                        </p>
                    </div>
                </div>
            )}

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Gallery</h1>
                        <p className="text-sm text-muted-foreground">{total} file{total !== 1 ? 's' : ''} total</p>
                    </div>

                    {/* Filters */}
                    <div className="flex flex-wrap items-center gap-2">
                        {/* Type filter */}
                        <Select
                            value={filters.type}
                            onValueChange={(v) => handleFilterChange('type', v)}
                        >
                            <SelectTrigger className="w-36" id="gallery-filter-type">
                                <SelectValue placeholder="Type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem value="image">
                                    <span className="flex items-center gap-2">
                                        <Image className="h-3.5 w-3.5" /> Images
                                    </span>
                                </SelectItem>
                                <SelectItem value="video">
                                    <span className="flex items-center gap-2">
                                        <FileVideo className="h-3.5 w-3.5" /> Videos
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        {/* Sort */}
                        <Select
                            value={filters.sort}
                            onValueChange={(v) => handleFilterChange('sort', v)}
                        >
                            <SelectTrigger className="w-44" id="gallery-filter-sort">
                                <SelectValue placeholder="Sort" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="date_desc">
                                    <span className="flex items-center gap-2">
                                        <ArrowDownZA className="h-3.5 w-3.5" /> Date (Newest)
                                    </span>
                                </SelectItem>
                                <SelectItem value="date_asc">
                                    <span className="flex items-center gap-2">
                                        <ArrowDownAZ className="h-3.5 w-3.5" /> Date (Oldest)
                                    </span>
                                </SelectItem>
                                <SelectItem value="size_desc">
                                    <span className="flex items-center gap-2">
                                        <ArrowUpDown className="h-3.5 w-3.5" /> Size (Largest)
                                    </span>
                                </SelectItem>
                                <SelectItem value="size_asc">
                                    <span className="flex items-center gap-2">
                                        <ArrowUpDown className="h-3.5 w-3.5" /> Size (Smallest)
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        {/* Group by */}
                        <Select
                            value={filters.group_by}
                            onValueChange={(v) => handleFilterChange('group_by', v)}
                        >
                            <SelectTrigger className="w-36" id="gallery-filter-group">
                                <SelectValue placeholder="Group" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="month">Group by Month</SelectItem>
                                <SelectItem value="year">Group by Year</SelectItem>
                            </SelectContent>
                        </Select>

                        {/* Select all */}
                        {allFiles.length > 0 && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={selectAll}
                                id="gallery-select-all"
                                className="gap-2"
                            >
                                {selected.size === allFiles.length ? (
                                    <CheckSquare2 className="h-4 w-4 text-primary" />
                                ) : (
                                    <Square className="h-4 w-4" />
                                )}
                                {selected.size === allFiles.length ? 'Deselect All' : 'Select All'}
                            </Button>
                        )}
                    </div>
                </div>

                {/* Empty state */}
                {allFiles.length === 0 && (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border border-dashed py-24 text-center">
                        <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-muted">
                            <Image className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">No media found</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Upload proof files through transactions to see them here.
                            </p>
                        </div>
                    </div>
                )}

                {/* Groups */}
                {groups.map((group) => (
                    <section key={group.label}>
                        <h2 className="mb-3 text-base font-semibold text-muted-foreground">{group.label}</h2>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            {group.files.map((file) => {
                                const isSelected = selected.has(file.path);

                                return (
                                    <div
                                        key={file.path}
                                        className={`group relative cursor-pointer overflow-hidden rounded-xl border-2 transition-all duration-200 ${
                                            isSelected
                                                ? 'border-primary shadow-lg shadow-primary/20'
                                                : 'border-transparent hover:border-muted-foreground/30'
                                        }`}
                                        onClick={() => openViewer(file, group)}
                                    >
                                        {/* Thumbnail */}
                                        <div className="aspect-square bg-muted">
                                            {file.type === 'image' ? (
                                                <img
                                                    src={file.url}
                                                    alt={file.name}
                                                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <div className="relative flex h-full w-full items-center justify-center bg-zinc-900">
                                                    <video
                                                        src={file.url}
                                                        className="h-full w-full object-cover opacity-70"
                                                        preload="metadata"
                                                        muted
                                                    />
                                                    <div className="absolute inset-0 flex items-center justify-center">
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                                            <FileVideo className="h-5 w-5 text-white" />
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>

                                        {/* Type badge */}
                                        <div className="absolute left-2 top-2">
                                            <span
                                                className={`flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm ${
                                                    file.type === 'image'
                                                        ? 'bg-blue-500/80 backdrop-blur-sm'
                                                        : 'bg-purple-500/80 backdrop-blur-sm'
                                                }`}
                                            >
                                                {file.type === 'image' ? (
                                                    <Image className="h-2.5 w-2.5" />
                                                ) : (
                                                    <FileVideo className="h-2.5 w-2.5" />
                                                )}
                                                {file.type === 'image' ? 'IMG' : 'VID'}
                                            </span>
                                        </div>

                                        {/* Checkbox */}
                                        <button
                                            id={`gallery-select-${file.path.replace(/\//g, '-')}`}
                                            className="absolute right-2 top-2 z-10 rounded-md bg-white/80 p-0.5 shadow backdrop-blur-sm transition-opacity duration-200 group-hover:opacity-100 dark:bg-zinc-800/80"
                                            style={{ opacity: isSelected ? 1 : undefined }}
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                toggleSelect(file.path);
                                            }}
                                            aria-label={isSelected ? 'Deselect' : 'Select'}
                                        >
                                            {isSelected ? (
                                                <CheckSquare2 className="h-4 w-4 text-primary" />
                                            ) : (
                                                <Square className="h-4 w-4 text-muted-foreground" />
                                            )}
                                        </button>

                                        {/* Bottom info */}
                                        <div className="bg-gradient-to-t from-black/60 to-transparent px-2 pb-2 pt-6 absolute bottom-0 left-0 right-0">
                                            <p className="truncate text-[11px] font-medium text-white/90">{file.name}</p>
                                            <p className="text-[10px] text-white/60">{formatFileSize(file.size)}</p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                ))}
            </div>

            {/* Floating action bar */}
            {selected.size > 0 && (
                <div className="fixed bottom-6 left-1/2 z-50 -translate-x-1/2">
                    <div className="flex items-center gap-3 rounded-2xl bg-white px-5 py-3 shadow-2xl ring-1 ring-black/10 dark:bg-zinc-900 dark:ring-white/10">
                        <span className="text-sm font-semibold">
                            {selected.size} selected
                        </span>
                        <div className="h-4 w-px bg-border" />
                        <Button
                            id="gallery-action-download"
                            variant="outline"
                            size="sm"
                            className="gap-2"
                            onClick={handleDownload}
                            disabled={isDownloading}
                        >
                            {isDownloading ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <Download className="h-4 w-4" />
                            )}
                            Download
                        </Button>
                        <Button
                            id="gallery-action-delete"
                            variant="destructive"
                            size="sm"
                            className="gap-2"
                            onClick={() => setShowDeleteConfirm(true)}
                        >
                            <Trash2 className="h-4 w-4" />
                            Delete
                        </Button>
                        <button
                            onClick={() => setSelected(new Set())}
                            className="ml-1 rounded-full p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                            aria-label="Clear selection"
                        >
                            <X className="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            )}

            {/* Delete confirmation dialog */}
            <Dialog open={showDeleteConfirm} onOpenChange={setShowDeleteConfirm}>
                <DialogContent>
                    <div className="flex items-start gap-4">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/10">
                            <AlertTriangle className="h-5 w-5 text-destructive" />
                        </div>
                        <div>
                            <DialogTitle>Delete {selected.size} file{selected.size !== 1 ? 's' : ''}?</DialogTitle>
                            <DialogDescription className="mt-1">
                                You are about to permanently delete{' '}
                                <strong>{selected.size} file{selected.size !== 1 ? 's' : ''}</strong>{' '}
                                from storage. This will also remove any associated proof references from
                                transaction records. This action cannot be undone.
                            </DialogDescription>
                        </div>
                    </div>
                    <DialogFooter className="mt-4 gap-2 sm:gap-4">
                        <Button variant="secondary" onClick={() => setShowDeleteConfirm(false)}>
                            Cancel
                        </Button>
                        <Button
                            id="gallery-confirm-delete"
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={isDeleting}
                            className="gap-2"
                        >
                            {isDeleting && <Loader2 className="h-4 w-4 animate-spin" />}
                            Delete {selected.size} file{selected.size !== 1 ? 's' : ''}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Media viewer modal */}
            <Dialog open={!!viewerFile} onOpenChange={(open) => !open && closeViewer()}>
                <DialogContent className="max-w-5xl border-0 bg-black/95 p-0 text-white shadow-2xl">
                    <DialogTitle className="sr-only">Media viewer</DialogTitle>
                    <DialogDescription className="sr-only">
                        View media file. Use arrow keys to navigate.
                    </DialogDescription>

                    {/* Close button */}
                    <button
                        onClick={closeViewer}
                        id="gallery-viewer-close"
                        className="absolute right-3 top-3 z-10 rounded-full bg-white/10 p-1.5 backdrop-blur-sm hover:bg-white/20"
                        aria-label="Close viewer"
                    >
                        <X className="h-5 w-5 text-white" />
                    </button>

                    {/* Navigation */}
                    {viewerGroup && viewerGroup.files.length > 1 && (
                        <>
                            <button
                                onClick={() => navigateViewer('prev')}
                                id="gallery-viewer-prev"
                                disabled={viewerIdx === 0}
                                className="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-2 backdrop-blur-sm hover:bg-white/20 disabled:opacity-30"
                                aria-label="Previous file"
                            >
                                <ChevronLeft className="h-6 w-6 text-white" />
                            </button>
                            <button
                                onClick={() => navigateViewer('next')}
                                id="gallery-viewer-next"
                                disabled={viewerGroup && viewerIdx === viewerGroup.files.length - 1}
                                className="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-2 backdrop-blur-sm hover:bg-white/20 disabled:opacity-30"
                                aria-label="Next file"
                            >
                                <ChevronRight className="h-6 w-6 text-white" />
                            </button>
                        </>
                    )}

                    {/* Media */}
                    <div className="flex min-h-[60vh] items-center justify-center p-4">
                        {viewerFile?.type === 'image' ? (
                            <img
                                src={viewerFile.url}
                                alt={viewerFile.name}
                                className="max-h-[80vh] max-w-full rounded-lg object-contain"
                            />
                        ) : viewerFile ? (
                            <video
                                ref={videoRef}
                                src={viewerFile.url}
                                controls
                                className="max-h-[80vh] max-w-full rounded-lg"
                                autoPlay
                            />
                        ) : null}
                    </div>

                    {/* File info bar */}
                    {viewerFile && (
                        <div className="flex items-center justify-between border-t border-white/10 px-5 py-3">
                            <div className="flex items-center gap-3">
                                <span
                                    className={`flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${
                                        viewerFile.type === 'image'
                                            ? 'bg-blue-500/30 text-blue-300'
                                            : 'bg-purple-500/30 text-purple-300'
                                    }`}
                                >
                                    {viewerFile.type === 'image' ? (
                                        <Image className="h-3 w-3" />
                                    ) : (
                                        <FileVideo className="h-3 w-3" />
                                    )}
                                    {viewerFile.type === 'image' ? 'Image' : 'Video'}
                                </span>
                                <span className="max-w-xs truncate text-sm font-medium text-white/80">
                                    {viewerFile.name}
                                </span>
                                <span className="text-xs text-white/40">{formatFileSize(viewerFile.size)}</span>
                            </div>
                            {viewerGroup && viewerGroup.files.length > 1 && (
                                <span className="text-xs text-white/40">
                                    {viewerIdx + 1} / {viewerGroup.files.length}
                                </span>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

Index.layout = {
    breadcrumbs: [{ title: 'Gallery', href: '/gallery' }],
};
