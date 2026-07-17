<?php

declare(strict_types=1);

/**
 * Store a CMS image as a metadata-free WebP and register it in cms_media.
 *
 * @return array{id:int,public_path:string,mime_type:string,width:int,height:int,size_bytes:int}
 */
function admin_store_image(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException(admin_upload_error($error));
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new InvalidArgumentException('The uploaded image could not be verified.');
    }
    if ($size < 1 || $size > 10 * 1024 * 1024) {
        throw new InvalidArgumentException('Images must be no larger than 10 MiB.');
    }
    if (!extension_loaded('fileinfo') || !extension_loaded('gd') || !function_exists('imagewebp')) {
        throw new RuntimeException('Image uploads require the Fileinfo and GD PHP extensions.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    $loaders = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/webp' => 'imagecreatefromwebp',
    ];
    if (!isset($loaders[$mime])) {
        throw new InvalidArgumentException('Only JPEG, PNG, and WebP images are accepted.');
    }

    $dimensions = @getimagesize($tmp);
    $width = (int) ($dimensions[0] ?? 0);
    $height = (int) ($dimensions[1] ?? 0);
    if ($width < 1 || $height < 1 || $width * $height > 30_000_000) {
        throw new InvalidArgumentException('The image dimensions are invalid or exceed 30 megapixels.');
    }

    $loader = $loaders[$mime];
    $source = @$loader($tmp);
    if (!$source instanceof GdImage) {
        throw new InvalidArgumentException('The image is corrupt or could not be decoded.');
    }

    $scale = min(1, 3200 / max($width, $height));
    $targetWidth = max(1, (int) round($width * $scale));
    $targetHeight = max(1, (int) round($height * $scale));
    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    if (!$target instanceof GdImage) {
        imagedestroy($source);
        throw new RuntimeException('The server could not allocate memory for this image.');
    }

    imagealphablending($target, false);
    imagesavealpha($target, true);
    $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
    imagefill($target, 0, 0, $transparent);
    if (!imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
        imagedestroy($source);
        imagedestroy($target);
        throw new RuntimeException('The server could not resize this image.');
    }
    imagedestroy($source);

    $relativeDirectory = 'media/cms/' . gmdate('Y/m');
    $publicRoot = dirname(__DIR__);
    $directory = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        imagedestroy($target);
        throw new RuntimeException('The CMS media directory is not writable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.webp';
    $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!imagewebp($target, $absolutePath, 86)) {
        imagedestroy($target);
        throw new RuntimeException('The image could not be written.');
    }
    imagedestroy($target);
    @chmod($absolutePath, 0644);

    $publicPath = '/' . $relativeDirectory . '/' . $filename;
    $outputSize = filesize($absolutePath);
    if ($outputSize === false) {
        @unlink($absolutePath);
        throw new RuntimeException('The stored image could not be verified.');
    }

    try {
        $statement = db()->prepare(
            'INSERT INTO cms_media (public_path, mime_type, width, height, size_bytes, created_at, version)
             VALUES (:path, :mime, :width, :height, :size, UTC_TIMESTAMP(), 1)'
        );
        $statement->execute([
            'path' => $publicPath,
            'mime' => 'image/webp',
            'width' => $targetWidth,
            'height' => $targetHeight,
            'size' => $outputSize,
        ]);
        $id = (int) db()->lastInsertId();
    } catch (Throwable $exception) {
        @unlink($absolutePath);
        throw $exception;
    }

    return [
        'id' => $id,
        'public_path' => $publicPath,
        'mime_type' => 'image/webp',
        'width' => $targetWidth,
        'height' => $targetHeight,
        'size_bytes' => (int) $outputSize,
    ];
}

/** @return list<array<string,mixed>> */
function admin_normalize_uploads(array $files): array
{
    if (!is_array($files['name'] ?? null)) {
        return [$files];
    }

    $normalized = [];
    foreach ($files['name'] as $index => $name) {
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}

function admin_upload_error(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image exceeds the server upload limit.',
        UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Choose an image to upload.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'The server could not accept the image.',
        default => 'The image upload failed.',
    };
}
