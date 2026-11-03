<?php

namespace App\Models;

use CodeIgniter\Model;

class HandoverDetailModel extends Model
{
    protected $table            = 'handover_details';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'handover_id',
        'booking_item_id',
        'resource_id',
        'quantity',
        'condition_status',
        'condition_notes',
        'meter_value',
        'accessory_notes',
        'photo_path',
    ];

    protected $useTimestamps = false; // created_at defaults to current_timestamp()

    public function getByHandoverId(int $handoverId): array
    {
        return $this->where('handover_id', $handoverId)->findAll();
    }
}

