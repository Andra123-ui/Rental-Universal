<?php

namespace App\Controllers\Customer;

use App\Libraries\CustomerUploads;
use App\Models\ActivityLogModel;
use App\Models\CustomerDocumentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class IdentityDocuments extends BaseCustomerController
{
    protected CustomerDocumentModel $documentModel;
    protected CustomerUploads $uploads;
    protected ActivityLogModel $activityLogModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->documentModel    = new CustomerDocumentModel();
        $this->uploads          = new CustomerUploads();
        $this->activityLogModel = new ActivityLogModel();
    }

    /**
     * GET /account/identity-documents (CUS-07: Dokumen Identitas)
     */
    public function index()
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login')->with('info', 'Silakan masuk terlebih dahulu.');
        }

        $documents = $this->documentModel->getByCustomer($customerId);

        return $this->renderCustomerView('customer/identity/index', [
            'activeNav'    => 'identity',
            'pageTitle'    => 'Dokumen Identitas',
            'pageSubtitle' => 'Kelola dokumen KTP, SIM, atau Paspor untuk keperluan verifikasi sewa unit.',
            'breadcrumbs'  => ['Dokumen Identitas' => null],
            'documents'    => $documents,
            'docTypes'     => [
                'KTP'    => 'KTP (Kartu Tanda Penduduk)',
                'SIM'    => 'SIM (Surat Izin Mengemudi)',
                'PASPOR' => 'Paspor',
            ],
        ]);
    }

    /**
     * POST /account/identity-documents/upload
     * Unggah dokumen identitas baru atau ganti dokumen lama
     */
    public function upload(): RedirectResponse
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $rules = [
            'document_type'   => 'required|in_list[KTP,SIM,PASPOR]',
            'document_number' => 'required|min_length[5]|max_length[100]',
            'expires_at'      => 'permit_empty|valid_date[Y-m-d]',
            'document_notes'  => 'permit_empty|max_length[255]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $docType   = strtoupper(trim((string) $this->request->getPost('document_type')));
        $docNumber = trim((string) $this->request->getPost('document_number'));
        $expiresAt = trim((string) $this->request->getPost('expires_at'));
        $docNotes  = trim((string) $this->request->getPost('document_notes'));

        $file = $this->request->getFile('doc_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->withInput()->with('error', 'File dokumen identitas wajib diunggah.');
        }

        // Cek apakah sudah ada dokumen dengan tipe ini
        $existing = $this->documentModel
            ->where('customer_id', $customerId)
            ->where('document_type', $docType)
            ->first();

        $oldFilePath = $existing['file_path'] ?? null;
        [$savedPath, $uploadError] = $this->uploads->saveIdentity($file, $customerId, $oldFilePath);

        if ($uploadError) {
            return redirect()->back()->withInput()->with('error', $uploadError);
        }

        if (!$savedPath) {
            return redirect()->back()->withInput()->with('error', 'Gagal memproses file dokumen.');
        }

        $docData = [
            'customer_id'         => $customerId,
            'document_type'       => $docType,
            'document_number'     => $docNumber,
            'file_path'           => $savedPath,
            'expires_at'          => !empty($expiresAt) ? $expiresAt : null,
            'is_verified'         => 0,
            'verification_status' => 'PENDING',
            'verified_at'         => null,
            'verified_by'         => null,
            'reject_reason'       => null,
            'notes'               => !empty($docNotes) ? $docNotes : null,
        ];

        if ($existing) {
            $this->documentModel->update($existing['id'], $docData);
        } else {
            $this->documentModel->insert($docData);
        }

        // Update ringkasan ID di tabel customers juga
        $this->customerModel->update($customerId, [
            'id_type'   => $docType,
            'id_number' => $docNumber,
        ]);

        $this->activityLogModel->record(
            ActivityLogModel::EVENT_DOCUMENT_UPLOAD,
            $this->customerAccountId(),
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent(),
            "Unggah dokumen {$docType} status PENDING"
        );

        return redirect()->to(site_url('account/identity-documents'))
            ->with('success', 'Dokumen identitas berhasil diunggah dan sedang dalam antrean verifikasi staf.');
    }

    /**
     * POST /account/identity-documents/delete/{id}
     * Hapus dokumen identitas
     */
    public function delete(int $id): RedirectResponse
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $doc = $this->documentModel->findCustomerDocument($id, $customerId);
        if (!$doc) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        // Hapus file fisik jika ada
        $this->uploads->deleteIdentity($doc['file_path'] ?? null);

        // Hapus row
        $this->documentModel->delete($id);

        return redirect()->to(site_url('account/identity-documents'))
            ->with('success', 'Dokumen identitas berhasil dihapus.');
    }

    /**
     * GET /account/identity-documents/file/{id}
     * Streaming file dokumen terproteksi (hanya pemilik yang dapat melihat)
     */
    public function file(int $id)
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return $this->response->setStatusCode(403);
        }

        $doc = $this->documentModel->findCustomerDocument($id, $customerId);
        if (!$doc) {
            throw PageNotFoundException::forPageNotFound('Dokumen tidak ditemukan.');
        }

        return $this->uploads->streamIdentity($doc['file_path']);
    }
}

