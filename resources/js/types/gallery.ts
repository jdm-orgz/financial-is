export interface GalleryFile {
    path: string;
    url: string;
    name: string;
    size: number;
    type: 'image' | 'video';
    created_at: string;
}

export interface GalleryGroup {
    label: string;
    files: GalleryFile[];
}

export interface GalleryFilters {
    type: 'all' | 'image' | 'video';
    sort: 'date_desc' | 'date_asc' | 'size_desc' | 'size_asc';
    group_by: 'month' | 'year';
}
