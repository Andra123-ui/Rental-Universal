<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

class CustomerUploads
{
    public const PHOTO_MAX_KB    = 2048;
    public const IDENTITY_MAX_KB = 4096;

    /**
     * Folder foto profil (di dalam writable, tidak bisa diakses publik langsung)
     * writable/uploads/profile/{customer_id}/
     */
    public function photoDir(int $customerId): string
    {
        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . $customerId . DIRECTORY_SEPARATOR;
    }

    /**
     * Folder file identitas.
     * writable/uploads/customer_documents/{customer_id}/
     */
    public function identityDir(int $customerId): string
    {
        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'customer_documents' . DIRECTORY_SEPARATOR . $customerId . DIRECTORY_SEPARATOR;
    }

    public function savePhoto(?UploadedFile $file, int $customerId, ?string $old = null): array
    {
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if (! $file->isValid() || $file->hasMoved()) {
            return [null, 'Foto gagal diunggah. Coba pilih ulang.'];
        }
        if ($file->getSizeByUnit('kb') > self::PHOTO_MAX_KB) {
            return [null, 'Ukuran foto maksimal 2 MB.'];
        }
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)
            || @getimagesize($file->getRealPath()) === false) {
            return [null, 'Foto harus berupa JPG, PNG, atau WEBP.'];
        }

        $dir = $this->photoDir($customerId);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $file->getRandomName();
        $file->move($dir, $name);

        $this->deleteFile($dir, $old);

        // Simpan path relatif: uploads/profile/{id}/namafile.jpg
        return ['uploads/profile/' . $customerId . '/' . $name, null];
    }

    public function saveIdentity(?UploadedFile $file, int $customerId, ?string $old = null): array
    {
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if (! $file->isValid() || $file->hasMoved()) {
            return [null, 'File identitas gagal diunggah. Coba pilih ulang.'];
        }
        if ($file->getSizeByUnit('kb') > self::IDENTITY_MAX_KB) {
            return [null, 'Ukuran file identitas maksimal 4 MB.'];
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
            return [null, 'File identitas harus JPG, PNG, atau PDF.'];
        }
        if ($mime !== 'application/pdf') {
            $info = @getimagesize($file->getRealPath());
            if ($info === false) {
                return [null, 'File bukan gambar yang valid.'];
            }
            if ($info[0] < 300 || $info[1] < 200) {
                return [null, 'Resolusi foto terlalu kecil, pastikan tulisan di identitas terbaca jelas.'];
            }
        } elseif (strncmp((string) file_get_contents($file->getRealPath(), false, null, 0, 5), '%PDF-', 5) !== 0) {
            return [null, 'File PDF tidak valid.'];
        }

        $dir = $this->identityDir($customerId);
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $name = $file->getRandomName();
        $file->move($dir, $name);

        $this->deleteIdentity($old);

        // Simpan path relatif: uploads/customer_documents/{id}/namafile.pdf
        return ['uploads/customer_documents/' . $customerId . '/' . $name, null];
    }

        public function documentFields(?array $doc, string $type, string $number, ?string $newFile, ?string $expiresAt): ?array
    {
        $type   = trim($type);
        $number = trim($number);
        $file   = $newFile ?? ($doc['file_path'] ?? null);

        if ($doc === null && $newFile === null) {
            return null;
        }

        $fields = [
            'document_type'   => $type,
            'document_number' => $number !== '' ? $number : null,
            'expires_at'      => $expiresAt ?: null,
        ];
        if ($newFile !== null) {
            $fields['file_path'] = $newFile;
        }

        // PERBAIKAN DISINI: 
        // Jika ada file baru diunggah, anggap sebagai perubahan (changed)
        $changed = $doc === null
            || $type !== (string) ($doc['document_type'] ?? '')
            || $number !== (string) ($doc['document_number'] ?? '')
            || $newFile !== null; // <--- Memaksa status PENDING jika upload file baru

        if ($type === '' || $number === '' || empty($file)) {
            $status = 'UNVERIFIED';
        } elseif ($changed) {
            $status = 'PENDING'; // Status kembali ke PENDING (menunggu verifikasi admin)
        } else {
            $status = $doc['verification_status'] ?? 'PENDING';
        }

        $fields['verification_status'] = $status;
        $fields['is_verified']         = $status === 'VERIFIED' ? 1 : 0;
        
        // Jika status PENDING (atau bukan VERIFIED) karena ada perubahan/file baru,
        // maka hapus jejak verifikasi lama (verified_at, verified_by)
        if ($status !== 'VERIFIED' && ($status !== ($doc['verification_status'] ?? null) || $changed)) {
            $fields['verified_at']   = null;
            $fields['verified_by']   = null;
            $fields['reject_reason'] = null;
        }

        return $fields;
    }

    public function photoPath(?string $relPath): ?string
    {
        if (! $relPath) {
            return null;
        }
        
        $prefix = 'uploads/profile/';
        $suffix = str_starts_with($relPath, $prefix) ? substr($relPath, strlen($prefix)) : $relPath;
        
        $path = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . $suffix;

        if (is_file($path)) {
            return $path;
        }

        return null;
    }

    public function identityPath(?string $relPath): ?string
    {
        if (! $relPath) {
            return null;
        }
        
        $prefix = 'uploads/customer_documents/';
        $suffix = str_starts_with($relPath, $prefix) ? substr($relPath, strlen($prefix)) : $relPath;
        
        $path = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'customer_documents' . DIRECTORY_SEPARATOR . $suffix;

        if (is_file($path)) {
            return $path;
        }

        return null;
    }

    public function streamPhoto(?string $relPath)
    {
        $path = $this->photoPath($relPath);
        $res  = service('response');
        if (! $path) {
            return $res->setStatusCode(404, 'Foto tidak ditemukan');
        }
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return $res
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="profile.' . pathinfo($path, PATHINFO_EXTENSION) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setBody(file_get_contents($path));
    }

    public function streamIdentity(?string $relPath)
    {
        $path = $this->identityPath($relPath);
        $res  = service('response');
        if (! $path) {
            return $res->setStatusCode(404, 'File tidak ditemukan');
        }
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return $res
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="identitas.' . pathinfo($path, PATHINFO_EXTENSION) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody(file_get_contents($path));
    }

    public function deleteIdentity(?string $relPath): void
    {
        $path = $this->identityPath($relPath);
        if ($path) {
            @unlink($path);
        }
    }

    private function deleteFile(string $dir, ?string $name): void
    {
        $base = basename($name);
        if ($base && is_file($dir . $base)) {
            @unlink($dir . $base);
        }
    }
}