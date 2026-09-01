<?php
function upload_product_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    $maxBytes = 2 * 1024 * 1024;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) {
        throw new RuntimeException('Image must be 2 MB or smaller.');
    }

    $tmpName = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpName)) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }

    // Verify that PHP can actually parse the file as an image.
    if (@getimagesize($tmpName) === false) {
        throw new RuntimeException('Uploaded file is not a valid image.');
    }

    $uploadDir = __DIR__ . '/../assets/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Upload directory is unavailable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Unable to save uploaded image.');
    }

    return $filename;
}
