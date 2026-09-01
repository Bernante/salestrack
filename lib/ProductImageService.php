<?php
/**
 * ProductImageService: Centralized Product Image Handling
 * 
 * Encapsulates:
 * - Product image file upload & validation
 * - SVG fallback image generation
 * - Image path resolution
 * - MIME type & file extension verification
 */

class ProductImageService
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const UPLOAD_DIR = 'uploads/products/';

    /**
     * Get product image URL or fallback SVG Data URI
     */
    public function getImageUrl(?string $imagePath, string $productName = 'Product'): string
    {
        if (!empty($imagePath)) {
            $cleanPath = ltrim($imagePath, '/');
            $fullPath = __DIR__ . '/../' . $cleanPath;
            if (file_exists($fullPath)) {
                return '/' . $cleanPath;
            }
        }
        return $this->generateFallbackSvg($productName);
    }

    /**
     * Generate SVG fallback image for product
     */
    private function generateFallbackSvg(string $productName): string
    {
        $emoji = match (strtolower($productName)) {
            'egg'  => '🥚',
            'ice'  => '🧊',
            default => '📦',
        };

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">'
             . '<rect width="300" height="300" fill="#f8fafc"/>'
             . '<rect x="10" y="10" width="280" height="280" rx="16" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="2"/>'
             . '<text x="150" y="130" font-size="72" text-anchor="middle" dominant-baseline="central">' . $emoji . '</text>'
             . '<text x="150" y="210" font-family="sans-serif" font-size="20" font-weight="bold" fill="#475569" text-anchor="middle">' . htmlspecialchars($productName) . '</text>'
             . '<text x="150" y="240" font-family="sans-serif" font-size="12" fill="#94a3b8" text-anchor="middle">No Custom Image</text>'
             . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Securely handle product image file upload
     *
     * @throws Exception If upload validation fails
     */
    public function handleUpload(?array $fileInput): ?string
    {
        if (!$fileInput || !isset($fileInput['error']) || $fileInput['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($fileInput['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($fileInput['error']));
        }

        if ($fileInput['size'] > self::MAX_FILE_SIZE) {
            throw new Exception('Image file size must be less than 5MB.');
        }

        $mimeType = $this->detectMimeType($fileInput['tmp_name']);
        if (!array_key_exists($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new Exception('Invalid image format. Only JPG, PNG, and WebP images are allowed.');
        }

        $ext = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception('Invalid file extension.');
        }

        return $this->saveUploadedFile($fileInput['tmp_name'], self::ALLOWED_MIME_TYPES[$mimeType]);
    }

    /**
     * Detect file MIME type using finfo
     */
    private function detectMimeType(string $filePath): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);
        if ($mimeType === false) {
            throw new Exception('Could not determine file type.');
        }
        return $mimeType;
    }

    /**
     * Save uploaded file to storage directory
     */
    private function saveUploadedFile(string $tempPath, string $extension): string
    {
        $fileName = 'prod_' . bin2hex(random_bytes(10)) . '.' . $extension;
        $uploadDir = __DIR__ . '/../' . self::UPLOAD_DIR;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory.');
        }

        $targetPath = $uploadDir . $fileName;
        if (!move_uploaded_file($tempPath, $targetPath)) {
            throw new Exception('Failed to save uploaded image.');
        }

        return self::UPLOAD_DIR . $fileName;
    }

    /**
     * Get human-readable error message for PHP upload errors
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE   => 'Image exceeds the maximum size allowed by server.',
            UPLOAD_ERR_FORM_SIZE  => 'Image exceeds the maximum size in the form.',
            UPLOAD_ERR_PARTIAL    => 'Image upload was incomplete.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk.',
            UPLOAD_ERR_EXTENSION  => 'Image upload stopped by extension.',
            default               => 'Image upload failed.',
        };
    }
}
