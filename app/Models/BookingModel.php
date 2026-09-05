<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table         = 'bookings';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'invoice_no', 'business_id', 'branch_id', 'customer_id', 'booking_source',
        'start_at', 'end_at', 'customer_name_snapshot', 'customer_phone_snapshot',
        'customer_email_snapshot', 'fulfillment_method', 'pickup_address', 'return_address',
        'status', 'payment_status', 'subtotal', 'discount_total', 'charge_total', 'deposit_total',
        'grand_total', 'paid_total', 'balance_due', 'customer_notes', 'internal_notes',
        'expires_at', 'created_by_user_id',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}