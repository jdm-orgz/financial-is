import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';

interface EditProps {
    appName: string;
    appLogo: string | null;
    maxUploadSize: number;
}

export default function Edit({ appName, appLogo, maxUploadSize }: EditProps) {
    const { data, setData, post, processing, errors } = useForm<{
        app_name: string;
        app_logo: File | null;
        remove_logo: boolean;
    }>({
        app_name: appName || '',
        app_logo: null,
        remove_logo: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        // Since we are uploading a file, Inertia handles multipart/form-data automatically via post()
        post('/app-config', {
            onSuccess: () => {
                setData((prev) => ({
                    ...prev,
                    app_logo: null,
                    remove_logo: false
                }));
                // Also reset the file input DOM element since React can't fully control it
                const fileInput = document.getElementById('app_logo') as HTMLInputElement;
                if (fileInput) fileInput.value = '';
            }
        });
    };

    const { props } = usePage();
    const flash = props.flash as { success?: string };

    return (
        <>
            <Head title="App Configuration" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">App Configuration</h1>
                </div>

                {flash?.success && (
                    <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        {flash.success}
                    </div>
                )}

                <div className="rounded-md border p-6">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="app_name">App Name</Label>
                            <Input
                                id="app_name"
                                value={data.app_name}
                                onChange={(e) => setData('app_name', e.target.value)}
                                placeholder="Demo App"
                            />
                            {errors.app_name && <p className="text-sm text-destructive">{errors.app_name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="app_logo">App Logo</Label>
                            <Input
                                id="app_logo"
                                type="file"
                                accept="image/*"
                                onChange={(e) => {
                                    const file = e.target.files ? e.target.files[0] : null;
                                    if (file && file.size > maxUploadSize * 1024) {
                                        toast.error(`File is too large! Maximum allowed size is ${maxUploadSize >= 1024 ? (maxUploadSize / 1024).toFixed(1) + ' MB' : maxUploadSize + ' KB'}.`);
                                        e.target.value = ''; // Clear the input
                                        return;
                                    }
                                    setData((prev) => ({ ...prev, app_logo: file, remove_logo: false }));
                                }}
                            />
                            <p className="text-sm text-muted-foreground">
                                Leave blank if you don't want to change the logo. Max size: {maxUploadSize >= 1024 ? (maxUploadSize / 1024).toFixed(1) + ' MB' : maxUploadSize + ' KB'}.
                            </p>
                            {errors.app_logo && <p className="text-sm text-destructive">{errors.app_logo}</p>}
                            
                            {appLogo && !data.app_logo && !data.remove_logo && (
                                <div className="mt-4">
                                    <p className="text-sm font-medium mb-2">Current Logo:</p>
                                    <div className="flex items-end gap-4">
                                        <img src={`/storage/${appLogo}`} alt="Current App Logo" className="h-20 w-20 object-contain rounded border" />
                                        <Button 
                                            type="button" 
                                            variant="destructive" 
                                            size="sm"
                                            onClick={() => setData('remove_logo', true)}
                                        >
                                            Remove Logo
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {data.remove_logo && (
                                <p className="text-sm text-amber-600 mt-2 font-medium">Logo will be removed upon saving.</p>
                            )}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Save Configuration
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Master Data',
            href: '#',
        },
        {
            title: 'App Configuration',
            href: '/app-config',
        },
    ],
};
