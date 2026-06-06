import { Head, Link } from '@inertiajs/react';
import { ShieldAlert, AlertTriangle, FileQuestion, ServerCrash } from 'lucide-react';
import { JSX } from 'react';

interface ErrorPageProps {
    status: number;
}

export default function ErrorPage({ status }: ErrorPageProps) {
    const title =
        {
            503: '503: Service Unavailable',
            500: '500: Server Error',
            404: '404: Page Not Found',
            403: '403: Forbidden',
        }[status] || 'Error';

    const description =
        {
            503: 'Sorry, we are doing some maintenance. Please check back soon.',
            500: 'Whoops, something went wrong on our servers.',
            404: 'Sorry, the page you are looking for could not be found.',
            403: 'Sorry, you do not have permission to access this page.',
        }[status] || 'An unexpected error occurred.';

    const Icon =
        {
            503: AlertTriangle,
            500: ServerCrash,
            404: FileQuestion,
            403: ShieldAlert,
        }[status] || AlertTriangle;

    return (
        <div className="flex min-h-[100dvh] flex-col items-center justify-center bg-background px-4 py-12 sm:px-6 lg:px-8">
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-md flex-col items-center justify-center text-center">
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-muted">
                    <Icon className="h-10 w-10 text-muted-foreground" />
                </div>
                <h1 className="mt-6 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                    {title}
                </h1>
                <p className="mt-4 text-muted-foreground">{description}</p>
                <div className="mt-8 flex gap-4">
                    <Link
                        href="/"
                        className="inline-flex h-10 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                    >
                        Back to Home
                    </Link>
                </div>
            </div>
        </div>
    );
}

// Bypass the default AppLayout for the error page
ErrorPage.layout = (page: JSX.Element) => page;
