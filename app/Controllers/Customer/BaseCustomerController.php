<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\BusinessProfileModel;
use App\Models\CustomerAccountModel;
use App\Models\CustomerModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseCustomerController extends BaseController
{
    protected $helpers = ['customer', 'business', 'site', 'url', 'form'];

    protected CustomerModel $customerModel;
    protected CustomerAccountModel $accountModel;
    protected BusinessProfileModel $businessModel;

    protected ?array $customer = null;
    protected ?array $customerAccount = null;
    protected ?array $business = null;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->customerModel = new CustomerModel();
        $this->accountModel  = new CustomerAccountModel();
        $this->businessModel = new BusinessProfileModel();

        $customerId = (int) session()->get('customer_id');
        $accountId  = (int) session()->get('customer_account_id');

        if ($customerId > 0) {
            $this->customer = $this->customerModel->find($customerId);
        }

        if ($accountId > 0) {
            $this->customerAccount = $this->accountModel->find($accountId);
        }

        $this->business = $this->businessModel->where('is_active', 1)->first() ?? [
            'business_name' => 'Rental Universal',
            'phone'         => '+6289523586766',
            'email'         => 'support@rentaluniversal.test',
            'address'       => 'Jl. Raya Rental No. 123',
            'currency_code' => 'IDR',
        ];
    }

    /**
     * Dapatkan Customer ID yang sedang login
     */
    protected function customerId(): int
    {
        return (int) ($this->customer['id'] ?? session()->get('customer_id') ?? 0);
    }

    /**
     * Dapatkan Customer Account ID yang sedang login
     */
    protected function customerAccountId(): int
    {
        return (int) ($this->customerAccount['id'] ?? session()->get('customer_account_id') ?? 0);
    }

    /**
     * Helper untuk render view customer dengan menyertakan data global (customer, account, business, activeTab)
     */
    protected function renderCustomerView(string $viewPath, array $data = []): string
    {
        $defaultData = [
            'customer'        => $this->customer,
            'customerAccount' => $this->customerAccount,
            'business'        => $this->business,
            'activeNav'       => $data['activeNav'] ?? 'dashboard',
            'pageTitle'       => $data['pageTitle'] ?? 'Portal Pelanggan',
            'breadcrumbs'     => $data['breadcrumbs'] ?? [],
        ];

        return view($viewPath, array_merge($defaultData, $data));
    }
}
