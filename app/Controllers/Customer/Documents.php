<?php

namespace App\Controllers\Customer;

use App\Models\BookingDocumentModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Documents extends BaseCustomerController
{
    protected BookingModel $bookingModel;
    protected PaymentModel $paymentModel;
    protected BookingDocumentModel $documentModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->bookingModel  = new BookingModel();
        $this->paymentModel  = new PaymentModel();
        $this->documentModel = new BookingDocumentModel();
    }

    /**
     * GET /account/documents (CUS-05: Invoice & Dokumen)
     */
    public function index()
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login')->with('info', 'Silakan masuk terlebih dahulu.');
        }

        $typeFilter = $this->request->getGet('type');
        $search     = trim((string) $this->request->getGet('q'));

        // 1. Ambil dokumen resmi dari booking_documents
        $attachedDocs = $this->documentModel->getDocumentsByCustomer($customerId, $typeFilter);

        // 2. Ambil seluruh bookings milik customer untuk dijadikan daftar Invoice
        $bookingsQuery = $this->bookingModel->where('customer_id', $customerId);
        if ($search !== '') {
            $bookingsQuery->like('invoice_no', $search);
        }
        $bookings = $bookingsQuery->orderBy('created_at', 'DESC')->findAll();

        // 3. Ambil seluruh payments paid untuk dijadikan daftar Kwitansi
        $paidPayments = $this->paymentModel->getPaymentsByCustomer($customerId, 'PAID');

        // Satukan ke dalam satu daftar dokumen yang mudah difilter
        $unifiedDocuments = [];

        // Invoices
        if (empty($typeFilter) || $typeFilter === 'INVOICE') {
            foreach ($bookings as $b) {
                $unifiedDocuments[] = [
                    'id'          => $b['id'],
                    'type'        => 'INVOICE',
                    'type_label'  => 'Invoice Digital',
                    'title'       => 'Invoice ' . $b['invoice_no'],
                    'number'      => $b['invoice_no'],
                    'invoice_no'  => $b['invoice_no'],
                    'date'        => $b['created_at'],
                    'amount'      => $b['grand_total'],
                    'status'      => $b['status'],
                    'is_file'     => false,
                    'view_url'    => site_url('account/bookings/' . $b['invoice_no']),
                    'print_url'   => site_url('account/bookings/' . $b['invoice_no'] . '/print'),
                ];
            }
        }

        // Kwitansi / Receipts
        if (empty($typeFilter) || $typeFilter === 'RECEIPT') {
            foreach ($paidPayments as $p) {
                $unifiedDocuments[] = [
                    'id'          => $p['id'],
                    'type'        => 'RECEIPT',
                    'type_label'  => 'Kwitansi Pembayaran',
                    'title'       => 'Kwitansi ' . $p['payment_no'],
                    'number'      => $p['payment_no'],
                    'invoice_no'  => $p['invoice_no'],
                    'date'        => $p['paid_at'] ?? $p['created_at'],
                    'amount'      => $p['amount'],
                    'status'      => 'PAID',
                    'is_file'     => false,
                    'view_url'    => site_url('account/payments/receipt/' . $p['payment_no']),
                    'print_url'   => site_url('account/payments/receipt/' . $p['payment_no']),
                ];
            }
        }

        // Dokumen Terlampir (Kontrak, Surat Jalan, dll dari booking_documents)
        if (empty($typeFilter) || !in_array($typeFilter, ['INVOICE', 'RECEIPT'])) {
            foreach ($attachedDocs as $doc) {
                $unifiedDocuments[] = [
                    'id'          => $doc['id'],
                    'type'        => $doc['document_type'],
                    'type_label'  => ucwords(str_replace('_', ' ', strtolower($doc['document_type']))),
                    'title'       => ($doc['document_no'] ?: $doc['document_type']) . ' (' . $doc['invoice_no'] . ')',
                    'number'      => $doc['document_no'] ?: '-',
                    'invoice_no'  => $doc['invoice_no'],
                    'date'        => $doc['created_at'],
                    'amount'      => null,
                    'status'      => 'AVAILABLE',
                    'is_file'     => true,
                    'view_url'    => site_url('account/documents/download/' . $doc['id']),
                    'print_url'   => site_url('account/documents/download/' . $doc['id']),
                ];
            }
        }

        // Sort by date DESC
        usort($unifiedDocuments, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });

        return $this->renderCustomerView('customer/documents/index', [
            'activeNav'    => 'documents',
            'pageTitle'    => 'Invoice & Dokumen',
            'pageSubtitle' => 'Perpustakaan dokumen transaksi Anda: invoice sewa, kwitansi pembayaran, dan berkas operasional.',
            'breadcrumbs' => ['Invoice & Dokumen' => null],
            'documents'    => $unifiedDocuments,
            'typeFilter'   => $typeFilter,
            'search'       => $search,
        ]);
    }

    /**
     * GET /account/documents/download/{id}
     * Download / Stream file privat booking_documents dengan otorisasi ketat
     */
    public function download(int $id)
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $doc = $this->documentModel->findCustomerDocument($id, $customerId);
        if (!$doc) {
            throw PageNotFoundException::forPageNotFound('Dokumen tidak ditemukan atau Anda tidak memiliki hak akses.');
        }

        $relPath = $doc['file_path'];
        $fullPath = WRITEPATH . ltrim($relPath, '/\\');

        if (!is_file($fullPath)) {
            // Coba di direktori alternatif writable
            $fullPath = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . basename($relPath);
            if (!is_file($fullPath)) {
                throw PageNotFoundException::forPageNotFound('File dokumen fisik tidak ditemukan di server.');
            }
        }

        $mime = mime_content_type($fullPath) ?: 'application/pdf';
        $filename = basename($fullPath);

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody(file_get_contents($fullPath));
    }
}

