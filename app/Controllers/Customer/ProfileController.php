<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\CustomerModel;
use App\Models\CustomerDocumentModel;
use App\Models\CustomerAccountModel;
use App\Libraries\OtpService;
use App\Models\CustomerLoginOtpModel;
use CodeIgniter\HTTP\RedirectResponse;

class ProfileController extends BaseController
{
    protected CustomerModel $customerModel;
    protected CustomerDocumentModel $documentModel;
    protected CustomerAccountModel $accountModel;
    protected CustomerLoginOtpModel $otpModel;
    protected OtpService $otpService;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->documentModel = new CustomerDocumentModel();
        $this->accountModel  = new CustomerAccountModel();
        $this->otpModel   = new CustomerLoginOtpModel();
        $this->otpService = new OtpService();
    }

    /**
     * =========================================================
     * PROFILE
     * =========================================================
     */
    public function index()
    {
        $customerId = (int) session()->get('customer_id');

        if ($customerId <= 0) {
            return redirect()
                ->to('/account/login')
                ->with(
                    'error',
                    'Silakan masuk terlebih dahulu.'
                );
        }

        $customer = $this->customerModel->find($customerId);

        if (!$customer) {
            return redirect()
                ->to('/account/login')
                ->with(
                    'error',
                    'Data customer tidak ditemukan.'
                );
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
            return redirect()
                ->to('/account/login');
        }

        $customer = $this->customerModel->find($customerId);

        if (!$customer) {
            return redirect()
                ->to('/account/login')
                ->with(
                    'error',
                    'Data customer tidak ditemukan.'
                );
        }

        /*
         * =====================================================
         * INPUT
         * =====================================================
         */

        $name = trim(
            (string) $this->request->getPost('name')
        );

        $email = trim(
            (string) $this->request->getPost('email')
        );

        $address = trim(
            (string) $this->request->getPost('address')
        );

        $emergencyName = trim(
            (string) $this->request->getPost(
                'emergency_contact_name'
            )
        );

        $emergencyPhone = trim(
            (string) $this->request->getPost(
                'emergency_contact_phone'
            )
        );

        $notes = trim(
            (string) $this->request->getPost('notes')
        );

        /*
         * DOKUMEN
         */
        $documentType = strtoupper(
            trim(
                (string) $this->request->getPost(
                    'document_type'
                )
            )
        );

        $documentNumber = trim(
            (string) $this->request->getPost(
                'document_number'
            )
        );

        $expiresAt = trim(
            (string) $this->request->getPost(
                'expires_at'
            )
        );

        $documentNotes = trim(
            (string) $this->request->getPost(
                'document_notes'
            )
        );

        $errors = [];

        /*
         * =====================================================
         * VALIDASI NAMA
         * =====================================================
         */

        if ($name === '') {

            $errors['name'] =
                'Nama lengkap wajib diisi.';

        } elseif (mb_strlen($name) < 3) {

            $errors['name'] =
                'Nama lengkap minimal 3 karakter.';

        } elseif (mb_strlen($name) > 150) {

            $errors['name'] =
                'Nama lengkap maksimal 150 karakter.';
        }

        /*
         * =====================================================
         * VALIDASI EMAIL
         * =====================================================
         */

        if ($email !== '') {

            if (!filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )) {

                $errors['email'] =
                    'Format email tidak valid.';

            } elseif (mb_strlen($email) > 150) {

                $errors['email'] =
                    'Email maksimal 150 karakter.';
            }
        }

        /*
         * =====================================================
         * VALIDASI ALAMAT
         * =====================================================
         */

        if (mb_strlen($address) > 500) {

            $errors['address'] =
                'Alamat maksimal 500 karakter.';
        }

        /*
         * =====================================================
         * VALIDASI KONTAK DARURAT
         * =====================================================
         */

        if (
            $emergencyPhone !== '' &&
            $emergencyName === ''
        ) {

            $errors['emergency_contact_name'] =
                'Nama kontak darurat wajib diisi.';
        }

        if ($emergencyName !== '') {

            if (mb_strlen($emergencyName) < 3) {

                $errors['emergency_contact_name'] =
                    'Nama kontak darurat minimal 3 karakter.';

            } elseif (mb_strlen($emergencyName) > 150) {

                $errors['emergency_contact_name'] =
                    'Nama kontak darurat maksimal 150 karakter.';
            }
        }

        if (
            $emergencyName !== '' &&
            $emergencyPhone === ''
        ) {

            $errors['emergency_contact_phone'] =
                'Nomor kontak darurat wajib diisi.';
        }

        if ($emergencyPhone !== '') {

            if (!preg_match(
                '/^[0-9+][0-9\s\-()]{7,29}$/',
                $emergencyPhone
            )) {

                $errors['emergency_contact_phone'] =
                    'Format nomor kontak darurat tidak valid.';
            }
        }

        /*
         * =====================================================
         * VALIDASI CATATAN
         * =====================================================
         */

        if (mb_strlen($notes) > 500) {

            $errors['notes'] =
                'Catatan maksimal 500 karakter.';
        }

        /*
         * =====================================================
         * VALIDASI DOKUMEN
         * =====================================================
         */

        $allowedDocumentTypes = [
            'KTP',
            'SIM',
            'PASPOR',
        ];

        $hasDocumentData =
            $documentType !== '' ||
            $documentNumber !== '' ||
            $expiresAt !== '' ||
            $documentNotes !== '';

        $file = $this->request->getFile('document_file');

        $hasNewFile =
            $file &&
            $file->getError() !== UPLOAD_ERR_NO_FILE;

        if ($hasDocumentData || $hasNewFile) {

            /*
             * Jenis dokumen
             */
            if ($documentType === '') {

                $errors['document_type'] =
                    'Jenis identitas wajib dipilih.';

            } elseif (
                !in_array(
                    $documentType,
                    $allowedDocumentTypes,
                    true
                )
            ) {

                $errors['document_type'] =
                    'Jenis identitas tidak valid.';
            }

            /*
             * Nomor dokumen
             */
            if ($documentNumber === '') {

                $errors['document_number'] =
                    'Nomor identitas wajib diisi.';

            } elseif (mb_strlen($documentNumber) < 5) {

                $errors['document_number'] =
                    'Nomor identitas minimal 5 karakter.';

            } elseif (mb_strlen($documentNumber) > 100) {

                $errors['document_number'] =
                    'Nomor identitas maksimal 100 karakter.';
            }

            /*
             * File
             */
            if (!$hasNewFile) {

                /*
                 * Kalau sudah ada dokumen sebelumnya,
                 * file lama boleh dipertahankan.
                 */
                $existingDocument =
                    $this->documentModel
                        ->getLatestByCustomer($customerId);

                if (!$existingDocument) {

                    $errors['document_file'] =
                        'File identitas wajib diupload.';
                }

            } else {

                if (!$file->isValid()) {

                    $errors['document_file'] =
                        'File identitas tidak dapat diproses.';

                } else {

                    /*
                     * Maksimal 4 MB
                     */
                    if (
                        $file->getSizeByUnit('mb') > 4
                    ) {

                        $errors['document_file'] =
                            'Ukuran file maksimal 4 MB.';
                    }

                    /*
                     * Ekstensi
                     */
                    $extension = strtolower(
                        $file->getClientExtension()
                    );

                    $allowedExtensions = [
                        'jpg',
                        'jpeg',
                        'png',
                        'pdf',
                    ];

                    if (
                        !in_array(
                            $extension,
                            $allowedExtensions,
                            true
                        )
                    ) {

                        $errors['document_file'] =
                            'File harus berupa JPG, JPEG, PNG, atau PDF.';
                    }
                }
            }

            /*
             * Tanggal berlaku
             */
            if ($expiresAt !== '') {

                $date = \DateTime::createFromFormat(
                    'Y-m-d',
                    $expiresAt
                );

                $validDate =
                    $date &&
                    $date->format('Y-m-d') === $expiresAt;

                if (!$validDate) {

                    $errors['expires_at'] =
                        'Tanggal berlaku tidak valid.';
                }
            }

            /*
             * Catatan dokumen
             */
            if (mb_strlen($documentNotes) > 255) {

                $errors['document_notes'] =
                    'Catatan dokumen maksimal 255 karakter.';
            }
        }

        /*
         * =====================================================
         * JIKA VALIDASI GAGAL
         * =====================================================
         */

        if (!empty($errors)) {

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'errors',
                    $errors
                );
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
         * UPDATE CUSTOMER
         * =====================================================
         */

        $customerData = [
            'name' =>
                $name,

            'email' =>
                $email !== ''
                    ? $email
                    : null,

            'address' =>
                $address !== ''
                    ? $address
                    : null,

            'emergency_contact_name' =>
                $emergencyName !== ''
                    ? $emergencyName
                    : null,

            'emergency_contact_phone' =>
                $emergencyPhone !== ''
                    ? $emergencyPhone
                    : null,

            'notes' =>
                $notes !== ''
                    ? $notes
                    : null,
        ];

        /*
         * Tetap sinkronkan identitas ke customers
         * karena kolom id_type dan id_number memang
         * tersedia pada tabel customers.
         */
        if ($hasDocumentData || $hasNewFile) {

            $customerData['id_type'] =
                $documentType !== ''
                    ? $documentType
                    : null;

            $customerData['id_number'] =
                $documentNumber !== ''
                    ? $documentNumber
                    : null;
        }

        $this->customerModel->update(
            $customerId,
            $customerData
        );

        /*
         * =====================================================
         * SIMPAN DOCUMENT
         * =====================================================
         */

        if ($hasDocumentData || $hasNewFile) {

            $existingDocument =
                $this->documentModel
                    ->getLatestByCustomer(
                        $customerId
                    );

            $documentData = [
                'customer_id' =>
                    $customerId,

                'document_type' =>
                    $documentType,

                'document_number' =>
                    $documentNumber,

                'expires_at' =>
                    $expiresAt !== ''
                        ? $expiresAt
                        : null,

                'notes' =>
                    $documentNotes !== ''
                        ? $documentNotes
                        : null,
            ];

            /*
             * Jika upload file baru
             */
            if ($hasNewFile) {

                $uploadPath =
                    FCPATH .
                    'uploads/customer_documents';

                if (!is_dir($uploadPath)) {

                    mkdir(
                        $uploadPath,
                        0755,
                        true
                    );
                }

                $newName =
                    $file->getRandomName();

                if (!$file->move(
                    $uploadPath,
                    $newName
                )) {

                    $db->transRollback();

                    return redirect()
                        ->back()
                        ->withInput()
                        ->with(
                            'error',
                            'File identitas gagal disimpan.'
                        );
                }

                $documentData['file_path'] =
                    'uploads/customer_documents/' .
                    $newName;

                /*
                 * Dokumen baru dianggap belum diverifikasi.
                 */
                $documentData['is_verified'] = 0;

                /*
                 * Kalau sudah ada dokumen,
                 * update dokumen tersebut.
                 */
                if ($existingDocument) {

                    $this->documentModel->update(
                        $existingDocument['id'],
                        $documentData
                    );

                } else {

                    $this->documentModel->insert(
                        $documentData
                    );
                }

            } else {

                /*
                 * Tidak ada file baru.
                 *
                 * Jangan mengganti file_path lama.
                 */
                if ($existingDocument) {

                    unset(
                        $documentData['file_path']
                    );

                    $this->documentModel->update(
                        $existingDocument['id'],
                        $documentData
                    );
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
                ->with(
                    'error',
                    'Profil gagal disimpan.'
                );
        }

        return redirect()
            ->to('/account/profile')
            ->with(
                'success',
                'Profil berhasil diperbarui.'
            );
    }

    /**
     * =========================================================
     * SEND OTP GANTI NOMOR
     * =========================================================
     *
     * Untuk bagian OTP, sistem existing kamu sudah punya
     * AuthController + OtpService + customer_login_otps.
     *
     * Jadi jangan membuat OTP session baru.
     *
     * Di sini nomor baru disimpan ke session dahulu.
     */
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

    if (!$customer) {
        return redirect()
            ->back()
            ->with('error', 'Data customer tidak ditemukan.');
    }

    /*
     * =====================================================
     * NORMALISASI NOMOR BARU
     * =====================================================
     */

    $phone = $this->otpService->normalizePhone(
        (string) $this->request->getPost('phone')
    );

    if ($phone === null) {
        return redirect()
            ->back()
            ->with('phone_errors', [
                'phone' => 'Format nomor WhatsApp tidak valid.'
            ]);
    }

    /*
     * =====================================================
     * CEK NOMOR LAMA
     * =====================================================
     */

    $oldPhone = $this->otpService->normalizePhone(
        (string) ($customer['phone'] ?? '')
    );

    if ($phone === $oldPhone) {
        return redirect()
            ->back()
            ->with('phone_errors', [
                'phone' => 'Nomor WhatsApp baru harus berbeda dari nomor sekarang.'
            ]);
    }

    /*
     * =====================================================
     * CEK CUSTOMER LAIN
     * =====================================================
     */

    if ($this->phoneExists($phone, $customerId)) {
        return redirect()
            ->back()
            ->with('phone_errors', [
                'phone' => 'Nomor WhatsApp tersebut sudah digunakan.'
            ]);
    }

    /*
     * =====================================================
     * CEK CUSTOMER ACCOUNT
     * =====================================================
     */

    $existingAccount = $this->accountModel
        ->findByLoginPhone($phone);

    if ($existingAccount) {
        return redirect()
            ->back()
            ->with('phone_errors', [
                'phone' => 'Nomor WhatsApp tersebut sudah digunakan.'
            ]);
    }

    $ip = $this->request->getIPAddress();
    $userAgent = (string) $this->request->getUserAgent();

    /*
     * =====================================================
     * RATE LIMIT
     * =====================================================
     */

    if ($this->isRateLimited($phone, $ip)) {
        return redirect()
            ->back()
            ->with('phone_errors', [
                'phone' =>
                    'Terlalu banyak permintaan OTP. Silakan coba lagi nanti.'
            ]);
    }

    /*
     * =====================================================
     * CEK OTP PENDING
     * =====================================================
     */

    $pending = $this->otpModel
        ->latestPendingByPhone($phone);

    if ($pending !== null) {

        $createdAt = strtotime(
            $pending['created_at']
        );

        /*
         * Masih cooldown
         */
        if (
            time() - $createdAt
            < OtpService::RESEND_COOLDOWN_SECONDS
        ) {

            /*
             * Simpan status supaya modal tetap
             * masuk ke tahap OTP setelah redirect.
             */
            return redirect()
                ->back()
                ->with('phone_otp_sent', true);
        }

        /*
         * OTP lama sudah boleh diganti
         */
        $this->otpModel->markExpired(
            $pending['id']
        );
    }

    /*
     * =====================================================
     * SIMPAN NOMOR BARU KE SESSION
     * =====================================================
     *
     * BELUM masuk database.
     */

    session()->set(
        'profile_new_phone',
        $phone
    );

    /*
     * =====================================================
     * GENERATE OTP
     * =====================================================
     */

    $code = $this->otpService->generateCode();

    $codeHash = $this->otpService->hashCode(
        $code
    );

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

    /*
     * =====================================================
     * KIRIM OTP VIA WHATSAPP
     * =====================================================
     */

    $sent = $this->otpService->sendViaWhatsapp(
        $phone,
        $code
    );

    if (!$sent) {

        log_message(
            'error',
            "Gagal mengirim OTP perubahan nomor ke {$phone}"
        );

        return redirect()
            ->back()
            ->with(
                'phone_errors',
                [
                    'phone' =>
                        'Kode OTP gagal dikirim. Silakan coba lagi.'
                ]
            );
    }

    /*
     * =====================================================
     * SIMPAN EXPIRY
     * =====================================================
     */

    session()->set(
        'profile_phone_otp_expires_at',
        $expiresAt
    );

    /*
     * PENTING:
     * gunakan with() karena view membaca getFlashdata()
     */
    return redirect()
        ->back()
        ->with('phone_otp_sent', true)
        ->with(
            'success',
            'Kode OTP telah dikirim ke nomor WhatsApp baru.'
        );
}

public function verifyPhoneOtp(): RedirectResponse
{
    $customerId = (int) session()->get('customer_id');
    $accountId  = (int) session()->get('customer_account_id');

    if ($customerId <= 0 || $accountId <= 0) {
        return redirect()->to('/account/login');
    }

    /*
     * =====================================================
     * AMBIL NOMOR BARU DARI SESSION
     * =====================================================
     */

    $newPhone = session()->get(
        'profile_new_phone'
    );

    if (!$newPhone) {
        return redirect()
            ->back()
            ->with(
                'phone_otp_error',
                'Sesi perubahan nomor sudah berakhir. Silakan mulai lagi.'
            );
    }

    /*
     * =====================================================
     * AMBIL OTP
     * =====================================================
     */

    $otpCode = trim(
        (string) $this->request->getPost('otp')
    );

    if (!preg_match('/^[0-9]{6}$/', $otpCode)) {
        return redirect()
            ->back()
            ->with(
                'phone_otp_error',
                'Kode OTP harus terdiri dari 6 angka.'
            );
    }

    $otp = $this->otpModel
        ->latestPendingByAccountId($accountId);

    if ($otp === null) {
        return redirect()
            ->back()
            ->with(
                'phone_otp_error',
                'Kode OTP sudah tidak berlaku. Silakan minta kode baru.'
            );
    }

    /*
     * =====================================================
     * PASTIKAN OTP UNTUK NOMOR BARU
     * =====================================================
     */

    $otpPhone = $this->otpService->normalizePhone(
        (string) ($otp['phone'] ?? '')
    );

    if ($otpPhone !== $newPhone) {
        return redirect()
            ->back()
            ->with(
                'phone_otp_error',
                'Kode OTP tidak valid.'
            );
    }

    /*
     * =====================================================
     * CEK EXPIRED
     * =====================================================
     */

    if (
        empty($otp['expires_at']) ||
        strtotime($otp['expires_at']) < time()
    ) {

        $this->otpModel->markExpired(
            $otp['id']
        );

        return redirect()
            ->back()
            ->with(
                'phone_otp_error',
                'Kode OTP sudah tidak berlaku. Silakan minta kode baru.'
            );
    }

    /*
     * =====================================================
     * CEK MAX ATTEMPT
     * =====================================================
     */

    if (
        (int) $otp['attempt_count']
        >= (int) $otp['max_attempts']
    ) {

        $this->otpModel->markBlocked(
            $otp['id']
        );

        return redirect()
            ->back()
            ->with(
                'phone_otp_error',
                'Terlalu banyak percobaan salah. Silakan minta kode baru.'
            );
    }

    /*
     * =====================================================
     * VERIFY OTP
     * =====================================================
     */

    if (
        !$this->otpService->verifyCode(
            $otpCode,
            $otp['code_hash']
        )
    ) {

        $this->otpModel->incrementAttempt(
            $otp['id']
        );

        return redirect()
            ->back()
            ->with(
                'phone_otp_error',
                'Kode OTP salah. Silakan coba lagi.'
            );
    }

    /*
     * =====================================================
     * OTP BENAR
     * =====================================================
     */

    $db = db_connect();

    $db->transStart();

    /*
     * 1. Update customers.phone
     */
    $this->customerModel->update(
        $customerId,
        [
            'phone' => $newPhone,
        ]
    );

    /*
     * 2. Update customer_accounts.login_phone
     */
    $this->accountModel->update(
        $accountId,
        [
            'login_phone' =>
                $newPhone,

            'phone_verified_at' =>
                date('Y-m-d H:i:s'),
        ]
    );

    /*
     * 3. OTP hanya boleh digunakan sekali
     */
    $this->otpModel->markUsed(
        $otp['id']
    );

    $db->transComplete();

    /*
     * =====================================================
     * CEK TRANSACTION
     * =====================================================
     */

    if ($db->transStatus() === false) {

        return redirect()
            ->back()
            ->with(
                'phone_otp_error',
                'Nomor WhatsApp gagal diperbarui. Silakan coba lagi.'
            );
    }

    /*
     * =====================================================
     * BERSIHKAN SESSION
     * =====================================================
     */

    session()->remove([
        'profile_new_phone',
        'profile_phone_otp_sent',
        'profile_phone_otp_expires_at',
    ]);

    /*
     * customer_id dan customer_account_id
     * TIDAK perlu diubah karena ID tetap sama.
     */

    return redirect()
        ->to('/account/profile')
        ->with(
            'success',
            'Nomor WhatsApp berhasil diperbarui.'
        );
}

    /**
     * =========================================================
     * CEK NOMOR CUSTOMER
     * =========================================================
     */
    private function phoneExists(
        string $phone,
        int $exceptCustomerId = 0
    ): bool {

        $builder =
            $this->customerModel
                ->where('phone', $phone);

        if ($exceptCustomerId > 0) {

            $builder->where(
                'id !=',
                $exceptCustomerId
            );
        }

        return $builder->first() !== null;
    }

    /**
     * =========================================================
     * NORMALIZE PHONE
     * =========================================================
     */
    private function normalizePhone(
        string $phone
    ): string {

        $phone = trim($phone);

        $phone = preg_replace(
            '/[\s\-()]/',
            '',
            $phone
        );

        if (
            str_starts_with(
                $phone,
                '08'
            )
        ) {

            return '+62' .
                substr($phone, 1);
        }

        if (
            str_starts_with(
                $phone,
                '62'
            )
        ) {

            return '+' . $phone;
        }

        return $phone;
    }
}