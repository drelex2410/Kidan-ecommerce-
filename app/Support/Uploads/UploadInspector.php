<?php

namespace App\Support\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class UploadInspector
{
    public function inspectIncoming(UploadedFile $file): UploadInspectionResult
    {
        $this->guardAgainstUploadErrors($file);

        $originalExtension = Str::lower((string) $file->getClientOriginalExtension());
        $type = UploadStorage::typeForExtension($originalExtension);

        if ($type === null) {
            throw new UploadValidationException(
                translate('This file type is not supported.'),
                ['aiz_file' => [translate('This file type is not supported.')]]
            );
        }

        $size = (int) ($file->getSize() ?? 0);
        if ($size <= 0) {
            throw new UploadValidationException(
                translate('The uploaded file is empty.'),
                ['aiz_file' => [translate('The uploaded file is empty.')]]
            );
        }

        $maxSize = $type === 'image'
            ? (int) config('uploads.image_max_file_size_kb', 15360)
            : (int) config('uploads.max_file_size_kb', 15360);

        if ($size > ($maxSize * 1024)) {
            throw new UploadValidationException(
                translate('The file exceeds the maximum allowed size.'),
                ['aiz_file' => [translate('The file exceeds the maximum allowed size.')]]
            );
        }

        $realPath = $file->getRealPath();
        if (!$realPath || !is_file($realPath)) {
            throw new UploadValidationException(
                translate('The uploaded payload is invalid.'),
                ['aiz_file' => [translate('The uploaded payload is invalid.')]]
            );
        }

        $mimeType = (string) (mime_content_type($realPath) ?: $file->getMimeType() ?: '');
        if ($mimeType === '') {
            throw new UploadValidationException(
                translate('The uploaded file could not be inspected.'),
                ['aiz_file' => [translate('The uploaded file could not be inspected.')]]
            );
        }

        $displayName = $this->sanitizeDisplayName(
            pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME)
        );

        $hash = @hash_file('sha256', $realPath) ?: null;
        $width = null;
        $height = null;

        if ($type === 'image') {
            $this->validateImage($realPath, $originalExtension, $mimeType);

            if ($originalExtension !== 'svg') {
                $dimensions = @getimagesize($realPath);
                $width = (int) Arr::get($dimensions, 0);
                $height = (int) Arr::get($dimensions, 1);

                $maxWidth = (int) config('uploads.max_dimensions.width', 12000);
                $maxHeight = (int) config('uploads.max_dimensions.height', 12000);

                if ($width < 1 || $height < 1 || $width > $maxWidth || $height > $maxHeight) {
                    throw new UploadValidationException(
                        translate('The image dimensions are invalid.'),
                        ['aiz_file' => [translate('The image dimensions are invalid.')]]
                    );
                }
            }
        }

        return new UploadInspectionResult(
            type: $type,
            extension: $originalExtension,
            displayName: $displayName,
            mimeType: $mimeType,
            size: $size,
            hash: $hash,
            width: $width,
            height: $height,
        );
    }

    public function inspectStoredPath(string $path, string $type, string $extension): array
    {
        $exists = UploadStorage::exists($path);
        $absolutePath = UploadStorage::absolutePath($path);

        if (!$exists || !$absolutePath || !is_file($absolutePath)) {
            return [
                'exists' => false,
                'mime_type' => null,
                'size' => null,
                'hash' => null,
                'width' => null,
                'height' => null,
                'valid_image' => false,
                'error' => 'missing_file',
            ];
        }

        $mimeType = (string) (mime_content_type($absolutePath) ?: '');
        $size = (int) (filesize($absolutePath) ?: 0);
        $hash = @hash_file('sha256', $absolutePath) ?: null;
        $width = null;
        $height = null;
        $validImage = false;
        $error = null;

        if ($type === 'image') {
            try {
                $this->validateImage($absolutePath, Str::lower($extension), $mimeType);
                $validImage = true;

                if (Str::lower($extension) !== 'svg') {
                    $dimensions = @getimagesize($absolutePath);
                    $width = (int) Arr::get($dimensions, 0);
                    $height = (int) Arr::get($dimensions, 1);
                }
            } catch (UploadValidationException $exception) {
                $error = $exception->getMessage();
            }
        }

        return [
            'exists' => true,
            'mime_type' => $mimeType,
            'size' => $size,
            'hash' => $hash,
            'width' => $width,
            'height' => $height,
            'valid_image' => $validImage,
            'error' => $error,
        ];
    }

    public function sanitizeDisplayName(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/[^\pL\pN\-\_\s\.]+/u', '', $value) ?: '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?: '');

        return $value !== '' ? Str::limit($value, 190, '') : 'file';
    }

    protected function guardAgainstUploadErrors(UploadedFile $file): void
    {
        $error = $file->getError();

        if ($error === UPLOAD_ERR_OK && $file->isValid()) {
            return;
        }

        $message = match ($error) {
            UPLOAD_ERR_PARTIAL => translate('The upload was interrupted before it completed. Please try again on a stronger connection.'),
            UPLOAD_ERR_NO_FILE => translate('No file was uploaded.'),
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => translate('The file exceeds the maximum allowed size.'),
            default => translate('The uploaded payload is invalid.'),
        };

        throw new UploadValidationException($message, ['aiz_file' => [$message]]);
    }

    protected function validateImage(string $path, string $extension, string $mimeType): void
    {
        $allowedExtensions = config('uploads.allowed_image_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']);

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new UploadValidationException(
                translate('This image type is not supported.'),
                ['aiz_file' => [translate('This image type is not supported.')]]
            );
        }

        $allowedMimeTypes = (array) config("uploads.allowed_image_mime_types.{$extension}", []);
        if ($allowedMimeTypes !== [] && !in_array(Str::lower($mimeType), array_map('strtolower', $allowedMimeTypes), true)) {
            throw new UploadValidationException(
                translate('The uploaded image type does not match its file extension.'),
                ['aiz_file' => [translate('The uploaded image type does not match its file extension.')]]
            );
        }

        if ($extension === 'svg') {
            $this->validateSvg($path);
            return;
        }

        $imageInfo = @getimagesize($path);
        if ($imageInfo === false) {
            throw new UploadValidationException(
                translate('The uploaded file is not a valid image.'),
                ['aiz_file' => [translate('The uploaded file is not a valid image.')]]
            );
        }
    }

    protected function validateSvg(string $path): void
    {
        $contents = @file_get_contents($path);

        if (!is_string($contents) || trim($contents) === '') {
            throw new UploadValidationException(
                translate('The uploaded SVG is empty or corrupted.'),
                ['aiz_file' => [translate('The uploaded SVG is empty or corrupted.')]]
            );
        }

        if (!str_contains(Str::lower($contents), '<svg')) {
            throw new UploadValidationException(
                translate('The uploaded SVG is invalid.'),
                ['aiz_file' => [translate('The uploaded SVG is invalid.')]]
            );
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new \DOMDocument();
            if (!@$document->loadXML($contents, LIBXML_NONET)) {
                throw new RuntimeException('invalid_svg');
            }

            if ($document->getElementsByTagName('script')->length > 0) {
                throw new RuntimeException('unsafe_svg');
            }

            foreach ($document->getElementsByTagName('*') as $node) {
                if (!$node->hasAttributes()) {
                    continue;
                }

                foreach ($node->attributes as $attribute) {
                    $name = Str::lower($attribute->nodeName);
                    $value = Str::lower(trim((string) $attribute->nodeValue));

                    if (Str::startsWith($name, 'on')) {
                        throw new RuntimeException('unsafe_svg');
                    }

                    if (in_array($name, ['href', 'xlink:href'], true) && Str::startsWith($value, 'javascript:')) {
                        throw new RuntimeException('unsafe_svg');
                    }
                }
            }
        } catch (\Throwable) {
            throw new UploadValidationException(
                translate('The uploaded SVG failed security checks.'),
                ['aiz_file' => [translate('The uploaded SVG failed security checks.')]]
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
