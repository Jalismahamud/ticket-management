<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHelper
{
    /**
     * Upload a file and return its metadata.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @return array{file_name: string, file_path: string, file_size: int, mime_type: string}
     */
    public static function upload(UploadedFile $file, string $directory = 'attachments', string $disk = 'public'): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::random(24) . '.' . $extension;
        
        $path = $file->storeAs($directory, $safeName, $disk);

        return [
            'file_name' => $originalName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
        ];
    }

    /**
     * Delete an existing file safely if it exists.
     *
     * @param string|null $path
     * @param string $disk
     * @return bool
     */
    public static function delete(?string $path, string $disk = 'public'): bool
    {
        if (empty($path)) {
            return false;
        }

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Replace an old file with a new file.
     *
     * @param UploadedFile $newFile
     * @param string|null $oldPath
     * @param string $directory
     * @param string $disk
     * @return array{file_name: string, file_path: string, file_size: int, mime_type: string}
     */
    public static function update(UploadedFile $newFile, ?string $oldPath, string $directory = 'attachments', string $disk = 'public'): array
    {
        self::delete($oldPath, $disk);
        return self::upload($newFile, $directory, $disk);
    }

    /**
     * Format byte sizes into human readable format.
     *
     * @param int $bytes
     * @return string
     */
    public static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
