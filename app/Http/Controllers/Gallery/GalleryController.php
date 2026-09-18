<?php

namespace App\Http\Controllers\Gallery;

use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class GalleryController extends Controller
{
    /**
     * Display the gallery listing.
     */
    public function index(): \Inertia\Response
    {
        $type = request('type', 'all');
        $sort = request('sort', 'date_desc');
        $groupBy = request('group_by', 'month');

        $files = $this->scanFiles($type, $sort);

        $grouped = $this->groupFiles($files, $groupBy);

        return Inertia::render('Gallery/Index', [
            'groups' => $grouped,
            'filters' => [
                'type' => $type,
                'sort' => $sort,
                'group_by' => $groupBy,
            ],
            'total' => count($files),
        ]);
    }

    /**
     * Download selected files as a ZIP archive.
     */
    public function download(Request $request): BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
    {
        $validated = $request->validate([
            'paths' => ['required', 'array', 'min:1'],
            'paths.*' => ['required', 'string'],
        ]);

        $paths = $validated['paths'];

        $zipName = 'gallery_'.now()->format('Ymd_His').'.zip';
        $zipPath = storage_path('app/private/tmp/'.$zipName);

        if (! is_dir(storage_path('app/private/tmp'))) {
            mkdir(storage_path('app/private/tmp'), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP archive.');
        }

        $addedCount = 0;
        foreach ($paths as $relativePath) {
            $relativePath = ltrim($relativePath, '/');
            if (Storage::disk('public')->exists($relativePath)) {
                $fullPath = Storage::disk('public')->path($relativePath);
                $zip->addFile($fullPath, basename($relativePath));
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($zipPath);
            abort(422, 'None of the requested files were found.');
        }

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Delete selected files from storage and nullify DB references.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'paths' => ['required', 'array', 'min:1'],
            'paths.*' => ['required', 'string'],
        ]);

        $paths = $validated['paths'];

        foreach ($paths as $relativePath) {
            $relativePath = ltrim($relativePath, '/');

            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }

            // Nullify image proof reference in DB
            TransactionReplacementRealization::where('proof_image_path', $relativePath)
                ->update(['proof_image_path' => null]);

            // Nullify video proof reference in DB
            TransactionReplacementRealization::where('proof_video_path', $relativePath)
                ->update(['proof_video_path' => null]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($paths).' file(s) deleted successfully.',
        ]);

        return redirect()->back();
    }

    /**
     * Scan proofs/images and proofs/videos directories and return file metadata.
     *
     * @return array<int, array{path: string, url: string, name: string, size: int, type: string, created_at: Carbon}>
     */
    private function scanFiles(string $type, string $sort): array
    {
        $files = [];

        $directories = match ($type) {
            'image' => ['proofs/images', 'proofs/transfers'],
            'video' => ['proofs/videos'],
            default => ['proofs/images', 'proofs/transfers', 'proofs/videos'],
        };

        foreach ($directories as $dir) {
            $fileType = str_contains($dir, 'videos') ? 'video' : 'image';

            if (! Storage::disk('public')->exists($dir)) {
                continue;
            }

            $diskFiles = Storage::disk('public')->files($dir);

            foreach ($diskFiles as $relativePath) {
                $fullPath = Storage::disk('public')->path($relativePath);

                if (! file_exists($fullPath)) {
                    continue;
                }

                $files[] = [
                    'path' => $relativePath,
                    'url' => Storage::disk('public')->url($relativePath),
                    'name' => basename($relativePath),
                    'size' => Storage::disk('public')->size($relativePath),
                    'type' => $fileType,
                    'created_at' => Carbon::createFromTimestamp(filemtime($fullPath)),
                ];
            }
        }

        usort($files, function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'date_asc' => $a['created_at'] <=> $b['created_at'],
                'size_desc' => $b['size'] <=> $a['size'],
                'size_asc' => $a['size'] <=> $b['size'],
                default => $b['created_at'] <=> $a['created_at'], // date_desc
            };
        });

        return array_map(function (array $file): array {
            return [
                ...$file,
                'created_at' => $file['created_at']->toISOString(),
            ];
        }, $files);
    }

    /**
     * Group the flat file list by month or year.
     *
     * @param  array<int, array<string, mixed>>  $files
     * @return array<int, array{label: string, files: array<int, array<string, mixed>>}>
     */
    private function groupFiles(array $files, string $groupBy): array
    {
        $grouped = [];

        foreach ($files as $file) {
            $date = Carbon::parse($file['created_at']);
            $label = $groupBy === 'year' ? $date->format('Y') : $date->format('F Y');

            if (! isset($grouped[$label])) {
                $grouped[$label] = ['label' => $label, 'files' => []];
            }

            $grouped[$label]['files'][] = $file;
        }

        return array_values($grouped);
    }
}
