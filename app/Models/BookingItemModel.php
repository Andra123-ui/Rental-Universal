<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingItemModel extends Model
{
    protected $table         = 'booking_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'booking_id', 'catalog_item_id', 'item_name_snapshot', 'item_type_snapshot',
        'start_at', 'end_at', 'quantity', 'duration_value', 'duration_unit', 'unit_price',
        'discount_amount', 'subtotal', 'status', 'notes',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}