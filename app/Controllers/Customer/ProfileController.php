<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\CustomerModel;
use App\Models\CustomerDocumentModel;
use App\Models\CustomerAccountModel;
use App\Models\CustomerLoginOtpModel;
use App\Libraries\OtpService;
use App\Libraries\CustomerUploads;
use CodeIgniter\HTTP\RedirectResponse;

class ProfileController extends BaseController
{
    protected CustomerModel $customerModel;
    protected CustomerDocumentModel $documentModel;
    protected CustomerAccountModel $accountModel;
    protected CustomerLoginOtpModel $otpModel;
    protected OtpService $otpService;
    protected CustomerUploads $uploads;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->documentModel = new CustomerDocumentModel();
        $this->accountModel  = new CustomerAccountModel();
        $this->otpModel      = new CustomerLoginOtpModel();
        $this->otpService    = new OtpService();
        $this->uploads       = new CustomerUploads();
    }

    /**
     * =========================================================
     * PROFILE (TAMPILAN UTAMA)
     * =========================================================
     */
    public function index()
    {
        $customerId = (int) session()->get('customer_id');

        if ($customerId <= 0) {
            return redirect()
                ->to('/account/login')
                ->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $customer = $this->customerModel->find($customerId);

        if (! $customer) {
            return redirect()
                ->to('/account/login')
                ->with('error', 'Data customer tidak ditemukan.');
        }

        $document = $this->documentModel
            ->getLatestByCustomer($customerId);

        return view('customer/profile', [
            'customer' => $customer,
            'document' => $document,
            'docTypes' => [
                'KTP',
                'SIM',
                'PASPOR',
            ],
        ]);
    }

    /**
     * =========================================================
     * UPDATE PROFILE
     * =========================================================
     */
    public function update(): RedirectResponse
    {
        $customerId = (int) session()->get('customer_id');

        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $customer = $this->customerModel->find($customerId);

        if (! $customer) {
            return redirect()
                ->to('/account/login')
                ->with('error', 'Data customer tidak ditemukan.');
        }

        /*
         * =====================================================
         * INPUT
         * =====================================================
         */
        $name    = trim((string) $this->request->getPost('name'));
        $email   = trim((string) $this->request->getPost('email'));
        $address = trim((string) $this->request->getPost('address'));

        $emergencyName  = trim((string) $this->request->getPost('emergency_contact_name'));
        $emergencyPhone = trim((string) $this->request->getPost('emergency_contact_phone'));
        $notes          = trim((string) $this->request->getPost('notes'));

        /*
         * DOKUMEN
         */
        $documentType   = strtoupper(trim((string) $this->request->getPost('document_type')));
        $documentNumber = trim((string) $this->request->getPost('document_number'));
        $expiresAt      = trim((string) $this->request->getPost('expires_at'));
        $documentNotes  = trim((string) $this->request->getPost('document_notes'));

        $errors = [];

        /*
         * =====================================================
         * VALIDASI NAMA
         * =====================================================
         */
        if ($name === '') {
            $errors['name'] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($name) < 3) {
            $errors['name'] = 'Nama lengkap minimal 3 karakter.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Nama lengkap maksimal 150 karakter.';
        }

        /*
         * =====================================================
         * VALIDASI EMAIL
         * =====================================================
         */
        if ($email !== '') {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format email tidak valid.';
            } elseif (mb_strlen($email) > 150) {
                $errors['email'] = 'Email maksimal 150 karakter.';
            }
        }

        /*
         * =====================================================
         * VALIDASI ALAMAT
         * =====================================================
         */
        if (mb_strlen($address) > 500) {
            $errors['address'] = 'Alamat maksimal 500 karakter.';
        }

        /*
         * =====================================================
         * VALIDASI KONTAK DARURAT
         * =====================================================
         */
        if ($emergencyPhone !== '' && $emergencyName === '') {
            $errors['emergency_contact_name'] = 'Nama kontak darurat wajib diisi.';
        }

        if ($emergencyName !== '') {
            if (mb_strlen($emergencyName) < 3) {
                $errors['emergency_contact_name'] = 'Nama kontak darurat minimal 3 karakter.';
            } elseif (mb_strlen($emergencyName) > 150) {
                $errors['emergency_contact_name'] = 'Nama kontak darurat maksimal 150 karakter.';
            }
        }

        if ($emergencyName !== '' && $emergencyPhone === '') {
            $errors['emergency_contact_phone'] = 'Nomor kontak darurat wajib diisi.';
        }

        if ($emergencyPhone !== '') {
            if (! preg_match('/^[0-9+][0-9\s\-()]{7,29}$/', $emergencyPhone)) {
                $errors['emergency_contact_phone'] = 'Format nomor kontak darurat tidak valid.';
            }
        }

        /*
         * =====================================================
         * VALIDASI CATATAN
         * =====================================================
         */
        if (mb_strlen($notes) > 500) {
            $errors['notes'] = 'Catatan maksimal 500 karakter.';
        }

        /*
         * =====================================================
         * VALIDASI DOKUMEN
         * =====================================================
         */
        $allowedDocumentTypes = ['KTP', 'SIM', 'PASPOR'];

        $hasDocumentData =
            $documentType !== '' ||
            $documentNumber !== '' ||
            $expiresAt !== '' ||
            $documentNotes !== '';

        $file = $this->request->getFile('id_file');
        $hasNewFile = $file && $file->getError() !== UPLOAD_ERR_NO_FILE;

        if ($hasDocumentData || $hasNewFile) {

            if ($documentType === '') {
                $errors['document_type'] = 'Jenis identitas wajib dipilih.';
            } elseif (! in_array($documentType, $allowedDocumentTypes, true)) {
                $errors['document_type'] = 'Jenis identitas tidak valid.';
            }

            if ($documentNumber === '') {
                $errors['document_number'] = 'Nomor identitas wajib diisi.';
            } elseif (mb_strlen($documentNumber) < 5) {
                $errors['document_number'] = 'Nomor identitas minimal 5 karakter.';
            } elseif (mb_strlen($documentNumber) > 100) {
                $errors['document_number'] = 'Nomor identitas maksimal 100 karakter.';
            }

            if (! $hasNewFile) {
                $existingDocument = $this->documentModel->getLatestByCustomer($customerId);
                if (! $existingDocument) {
                    $errors['id_file'] = 'File identitas wajib diupload.';
                }
            }

            if ($expiresAt !== '') {
                $date = \DateTime::createFromFormat('Y-m-d', $expiresAt);
                $validDate = $date && $date->format('Y-m-d') === $expiresAt;
                if (! $validDate) {
                    $errors['expires_at'] = 'Tanggal berlaku tidak valid.';
                }
            }

            if (mb_strlen($documentNotes) > 255) {
                $errors['document_notes'] = 'Catatan dokumen maksimal 255 karakter.';
            }
        }

        /*
         * =====================================================
         * JIKA VALIDASI GAGAL
         * =====================================================
         */
        if (! empty($errors)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', $errors);
        }

        /*
         * =====================================================
         * TRANSACTION
         * =====================================================
         */
        $db = db_connect();
        $db->transStart();

        /*
         * =====================================================
         * SIMPAN FILE IDENTITAS BARU (ke writable/customer_documents)
         * =====================================================
         */
        $newFilePath = null;

        if ($hasNewFile) {
            $existingDocument = $this->documentModel->getLatestByCustomer($customerId);

            [$newFilePath, $fileError] = $this->uploads->saveIdentity(
                $file,
                $customerId, 
                $existingDocument['file_path'] ?? null
            );

            if ($fileError !== null) {
                $db->transRollback();

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('errors', ['id_file' => $fileError]);
            }
        }

        /*
         * =====================================================
         * SIMPAN FOTO PROFIL BARU (ke writable/profile)
         * =====================================================
         */
        $newPhotoName = null;
        $photoFile = $this->request->getFile('profile_photo');

        if ($photoFile && $photoFile->isValid() && ! $photoFile->hasMoved()) {
            [$newPhotoName, $photoError] = $this->uploads->savePhoto(
                $photoFile,
                $customerId, 
                $customer['profile_photo'] ?? null
            );

            if ($photoError !== null) {
                $db->transRollback();

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('errors', ['profile_photo' => $photoError]);
            }
        }

        /*
         * =====================================================
         * UPDATE CUSTOMER
         * =====================================================
         */
        $customerData = [
            'name'                    => $name,
            'email'                   => $email !== '' ? $email : null,
            'address'                 => $address !== '' ? $address : null,
            'emergency_contact_name'  => $emergencyName !== '' ? $emergencyName : null,
            'emergency_contact_phone' => $emergencyPhone !== '' ? $emergencyPhone : null,
            'notes'                   => $notes !== '' ? $notes : null,
        ];

        if ($newPhotoName !== null) {
            $customerData['profile_photo'] = $newPhotoName;
        }

        if ($hasDocumentData || $hasNewFile) {
            $customerData['id_type']   = $documentType !== '' ? $documentType : null;
            $customerData['id_number'] = $documentNumber !== '' ? $documentNumber : null;
        }

        $this->customerModel->update($customerId, $customerData);

        /*
         * =====================================================
         * SIMPAN DOCUMENT (ke tabel customer_documents)
         * =====================================================
         * Logika otomatis PENDING jika ada perubahan (termasuk file baru)
         * ada di dalam method documentFields() library CustomerUploads.
         */
                /*
         * =====================================================
         * SIMPAN DOCUMENT (ke tabel customer_documents)
         * =====================================================
         */
        if ($hasDocumentData || $hasNewFile) {

            $existingDocument = $this->documentModel->getLatestByCustomer($customerId);

            // Panggil documentFields. Jika $newFilePath ada isinya, status otomatis PENDING.
            $documentFields = $this->uploads->documentFields(
                $existingDocument,
                $documentType,
                $documentNumber,
                $newFilePath,
                $expiresAt !== '' ? $expiresAt : null
            );

            if ($documentFields !== null) {
                if ($existingDocument) {
                    // Gunakan Query Builder langsung untuk memastikan semua field terupdate
                    db_connect()->table('customer_documents')
                        ->where('id', $existingDocument['id'])
                        ->update($documentFields);
                } else {
                    $documentFields['customer_id'] = $customerId;
                    db_connect()->table('customer_documents')->insert($documentFields);
                }
            }
        }

        $db->transComplete();

        /*
         * =====================================================
         * CEK TRANSACTION
         * =====================================================
         */
        if ($db->transStatus() === false) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Profil gagal disimpan.');
        }

        return redirect()
            ->to('/account/profile')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * =========================================================
     * LIHAT FOTO PROFIL (via Controller, karena di writable)
     * =========================================================
     */
    public function profilePhoto()
    {
        $customerId = (int) session()->get('customer_id');

        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $customer = $this->customerModel->find($customerId);

        if (! $customer || empty($customer['profile_photo'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Foto profil tidak ditemukan.'
            );
        }

        return $this->uploads->streamPhoto($customer['profile_photo']);
    }

    /**
     * =========================================================
     * LIHAT FILE IDENTITAS
     * =========================================================
     */
    public function identityFile()
    {
        $customerId = (int) session()->get('customer_id');

        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $document = $this->documentModel->getLatestByCustomer($customerId);

        if (! $document || empty($document['file_path'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Dokumen identitas tidak ditemukan.'
            );
        }

        return $this->uploads->streamIdentity($document['file_path']);
    }

    /**
     * =========================================================
     * SEND OTP GANTI NOMOR
     * =========================================================
     */
    public function sendPhoneOtp(): RedirectResponse
    {
        $customerId = (int) session()->get('customer_id');
        $accountId  = (int) session()->get('customer_account_id');

        if ($customerId <= 0 || $accountId <= 0) {
            return redirect()->to('/account/login');
        }

        $customer = $this->customerModel->find($customerId);

        if (! $customer) {
            return redirect()
                ->back()
                ->with('error', 'Data customer tidak ditemukan.');
        }

        $phone = $this->otpService->normalizePhone(
            (string) $this->request->getPost('phone')
        );

        if ($phone === null) {
            return redirect()
                ->back()
                ->with('phone_errors', ['phone' => 'Format nomor WhatsApp tidak valid.']);
        }

        $oldPhone = $this->otpService->normalizePhone(
            (string) ($customer['phone'] ?? '')
        );

        if ($phone === $oldPhone) {
            return redirect()
                ->back()
                ->with('phone_errors', [
                    'phone' => 'Nomor WhatsApp baru harus berbeda dari nomor sekarang.',
                ]);
        }

        if ($this->phoneExists($phone, $customerId)) {
            return redirect()
                ->back()
                ->with('phone_errors', ['phone' => 'Nomor WhatsApp tersebut sudah digunakan.']);
        }

        $existingAccount = $this->accountModel->findByLoginPhone($phone);

        if ($existingAccount) {
            return redirect()
                ->back()
                ->with('phone_errors', ['phone' => 'Nomor WhatsApp tersebut sudah digunakan.']);
        }

        $ip        = $this->request->getIPAddress();
        $userAgent = (string) $this->request->getUserAgent();

        if ($this->isRateLimited($phone, $ip)) {
            return redirect()
                ->back()
                ->with('phone_errors', [
                    'phone' => 'Terlalu banyak permintaan OTP. Silakan coba lagi nanti.',
                ]);
        }

        $pending = $this->otpModel->latestPendingByPhone($phone);

        if ($pending !== null) {
            $createdAt = strtotime($pending['created_at']);

            if (time() - $createdAt < OtpService::RESEND_COOLDOWN_SECONDS) {
                return redirect()
                    ->back()
                    ->with('phone_otp_sent', true);
            }

            $this->otpModel->markExpired($pending['id']);
        }

        session()->set('profile_new_phone', $phone);

        $code     = $this->otpService->generateCode();
        $codeHash = $this->otpService->hashCode($code);

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + OtpService::OTP_TTL_SECONDS
        );

        $this->otpModel->insert([
            'customer_account_id' => $accountId,
            'phone'               => $phone,
            'purpose'             => CustomerLoginOtpModel::PURPOSE_LOGIN,
            'code_hash'           => $codeHash,
            'expires_at'          => $expiresAt,
            'attempt_count'       => 0,
            'max_attempts'        => 5,
            'status'              => CustomerLoginOtpModel::STATUS_PENDING,
            'requested_ip'        => $ip,
            'user_agent'          => $userAgent,
        ]);

        $sent = $this->otpService->sendViaWhatsapp($phone, $code);

        if (! $sent) {
            log_message('error', "Gagal mengirim OTP perubahan nomor ke {$phone}");

            return redirect()
                ->back()
                ->with('phone_errors', [
                    'phone' => 'Kode OTP gagal dikirim. Silakan coba lagi.',
                ]);
        }

        session()->set('profile_phone_otp_expires_at', $expiresAt);

        return redirect()
            ->back()
            ->with('phone_otp_sent', true)
            ->with('success', 'Kode OTP telah dikirim ke nomor WhatsApp baru.');
    }

    /**
     * =========================================================
     * VERIFY OTP GANTI NOMOR
     * =========================================================
     */
    public function verifyPhoneOtp(): RedirectResponse
    {
        $customerId = (int) session()->get('customer_id');
        $accountId  = (int) session()->get('customer_account_id');

        if ($customerId <= 0 || $accountId <= 0) {
            return redirect()->to('/account/login');
        }

        $newPhone = session()->get('profile_new_phone');

        if (! $newPhone) {
            return redirect()
                ->back()
                ->with(
                    'phone_otp_error',
                    'Sesi perubahan nomor sudah berakhir. Silakan mulai lagi.'
                );
        }

        $otpCode = trim((string) $this->request->getPost('otp'));

        if (! preg_match('/^[0-9]{6}$/', $otpCode)) {
            return redirect()
                ->back()
                ->with('phone_otp_error', 'Kode OTP harus terdiri dari 6 angka.');
        }

        $otp = $this->otpModel->latestPendingByAccountId($accountId);

        if ($otp === null) {
            return redirect()
                ->back()
                ->with(
                    'phone_otp_error',
                    'Kode OTP sudah tidak berlaku. Silakan minta kode baru.'
                );
        }

        $otpPhone = $this->otpService->normalizePhone(
            (string) ($otp['phone'] ?? '')
        );

        if ($otpPhone !== $newPhone) {
            return redirect()
                ->back()
                ->with('phone_otp_error', 'Kode OTP tidak valid.');
        }

        if (
            empty($otp['expires_at']) ||
            strtotime($otp['expires_at']) < time()
        ) {
            $this->otpModel->markExpired($otp['id']);

            return redirect()
                ->back()
                ->with(
                    'phone_otp_error',
                    'Kode OTP sudah tidak berlaku. Silakan minta kode baru.'
                );
        }

        if ((int) $otp['attempt_count'] >= (int) $otp['max_attempts']) {
            $this->otpModel->markBlocked($otp['id']);

            return redirect()
                ->back()
                ->with(
                    'phone_otp_error',
                    'Terlalu banyak percobaan salah. Silakan minta kode baru.'
                );
        }

        if (! $this->otpService->verifyCode($otpCode, $otp['code_hash'])) {
            $this->otpModel->incrementAttempt($otp['id']);

            return redirect()
                ->back()
                ->with('phone_otp_error', 'Kode OTP salah. Silakan coba lagi.');
        }

        $db = db_connect();
        $db->transStart();

        $this->customerModel->update($customerId, ['phone' => $newPhone]);

        $this->accountModel->update($accountId, [
            'login_phone'       => $newPhone,
            'phone_verified_at' => date('Y-m-d H:i:s'),
        ]);

        $this->otpModel->markUsed($otp['id']);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()
                ->back()
                ->with(
                    'phone_otp_error',
                    'Nomor WhatsApp gagal diperbarui. Silakan coba lagi.'
                );
        }

        session()->remove([
            'profile_new_phone',
            'profile_phone_otp_sent',
            'profile_phone_otp_expires_at',
        ]);

        return redirect()
            ->to('/account/profile')
            ->with('success', 'Nomor WhatsApp berhasil diperbarui.');
    }

    /**
     * =========================================================
     * CEK NOMOR CUSTOMER
     * =========================================================
     */
    private function phoneExists(string $phone, int $exceptCustomerId = 0): bool
    {
        $builder = $this->customerModel->where('phone', $phone);

        if ($exceptCustomerId > 0) {
            $builder->where('id !=', $exceptCustomerId);
        }

        return $builder->first() !== null;
    }

    /**
     * =========================================================
     * RATE LIMIT OTP
     * =========================================================
     */
    private function isRateLimited(string $phone, string $ip): bool
    {
        return false;
    }
}