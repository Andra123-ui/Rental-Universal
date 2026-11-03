<?php

namespace App\Controllers\Customer;

use App\Libraries\CustomerUploads;
use App\Models\ActivityLogModel;
use App\Models\CustomerDocumentModel;
use App\Models\CustomerModel;
use CodeIgniter\HTTP\RedirectResponse;

class Profile extends BaseCustomerController
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
     * GET /account/profile (CUS-06: Profil Saya)
     */
    public function index()
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login')->with('info', 'Silakan masuk terlebih dahulu.');
        }

        // Ambil dokumen identitas terbaru untuk ringkasan di profil
        $latestDocument = $this->documentModel->getLatestByCustomer($customerId);

        return $this->renderCustomerView('customer/profile/index', [
            'activeNav'      => 'profile',
            'pageTitle'      => 'Profil Saya',
            'pageSubtitle'   => 'Kelola informasi pribadi, kontak darurat, dan foto profil akun Anda.',
            'breadcrumbs'    => ['Profil Saya' => null],
            'customer'       => $this->customer,
            'latestDocument' => $latestDocument,
        ]);
    }

    /**
     * POST /account/profile/update
     * Simpan perubahan profil
     */
    public function update(): RedirectResponse
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $rules = [
            'name'                   => 'required|min_length[3]|max_length[150]',
            'email'                  => 'permit_empty|valid_email|max_length[150]',
            'address'                => 'permit_empty|max_length[500]',
            'emergency_contact_name' => 'permit_empty|min_length[3]|max_length[150]',
            'emergency_contact_phone'=> 'permit_empty|min_length[8]|max_length[30]',
            'notes'                  => 'permit_empty|max_length[500]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name           = trim((string) $this->request->getPost('name'));
        $email          = trim((string) $this->request->getPost('email'));
        $address        = trim((string) $this->request->getPost('address'));
        $emergencyName  = trim((string) $this->request->getPost('emergency_contact_name'));
        $emergencyPhone = trim((string) $this->request->getPost('emergency_contact_phone'));
        $notes          = trim((string) $this->request->getPost('notes'));

        $updateData = [
            'name'                    => $name,
            'email'                   => $email !== '' ? $email : null,
            'address'                 => $address !== '' ? $address : null,
            'emergency_contact_name'  => $emergencyName !== '' ? $emergencyName : null,
            'emergency_contact_phone' => $emergencyPhone !== '' ? $emergencyPhone : null,
            'notes'                   => $notes !== '' ? $notes : null,
        ];

        // Foto profil (opsional)
        $photoFile = $this->request->getFile('profile_photo');
        if ($photoFile && $photoFile->isValid() && !$photoFile->hasMoved()) {
            [$photoPath, $photoError] = $this->uploads->savePhoto($photoFile, $customerId, $this->customer['profile_photo'] ?? null);
            if ($photoError) {
                return redirect()->back()->withInput()->with('error', $photoError);
            }
            if ($photoPath) {
                $updateData['profile_photo'] = $photoPath;
            }
        }

        // Perbarui customer data (CATATAN: Snapshot transaksi pada tabel bookings tidak diubah)
        $this->customerModel->update($customerId, $updateData);

        // Catat activity log
        $this->activityLogModel->record(
            ActivityLogModel::EVENT_PROFILE_UPDATE,
            $this->customerAccountId(),
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent(),
            'Customer memperbarui data profil'
        );

        return redirect()->to(site_url('account/profile'))->with('success', 'Data profil Anda berhasil diperbarui.');
    }

    /**
     * GET /account/profile/photo
     * Stream foto profil pribadi
     */
    public function photo()
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return $this->response->setStatusCode(403);
        }

        $customer = $this->customerModel->find($customerId);
        $photoPath = $customer['profile_photo'] ?? null;

        return $this->uploads->streamPhoto($photoPath);
    }
}

