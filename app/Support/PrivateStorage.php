<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Private application uploads (recipient lists, sender docs, invoices, proofs).
 *
 * Uses the default filesystem disk so production can point at Cloudflare R2
 * via FILESYSTEM_DISK=r2 without changing call sites.
 */
final class PrivateStorage
{
    public static function name(): string
    {
        return (string) config('filesystems.default');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::name());
    }

    /**
     * Provide a local filesystem path for a stored object.
     *
     * Local disks return the real path. Cloud disks (R2/S3) are downloaded to
     * a temporary file that is deleted after the callback returns.
     *
     * @template TReturn
     *
     * @param  callable(string): TReturn  $callback
     * @return TReturn
     */
    public static function withLocalPath(string $path, callable $callback): mixed
    {
        $disk = self::disk();
        $driver = config('filesystems.disks.'.self::name().'.driver');

        if ($driver === 'local') {
            return $callback($disk->path($path));
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sms-'.Str::uuid()->toString()
            .($extension !== '' ? '.'.$extension : '');

        try {
            $stream = $disk->readStream($path);

            if ($stream === null) {
                throw new RuntimeException("Unable to read [{$path}] from storage.");
            }

            $destination = fopen($tempPath, 'w');

            if ($destination === false) {
                if (is_resource($stream)) {
                    fclose($stream);
                }

                throw new RuntimeException('Unable to create a temporary file for cloud storage.');
            }

            stream_copy_to_stream($stream, $destination);

            if (is_resource($stream)) {
                fclose($stream);
            }

            fclose($destination);

            return $callback($tempPath);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
}
