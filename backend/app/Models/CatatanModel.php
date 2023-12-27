<?php

namespace App\Models;

use CodeIgniter\Model;

class CatatanModel extends Model
{
    protected $table            = 'catatan';
    protected $primaryKey       = 'id_catatan';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id_user', 'id_folder', 'judul', 'isi', 'difavoritkan', 'dihapus', 'tgl_buat', 'tgl_edit', 'timestamp'];

    public function updateLastViewedAt($catatanId)
    {
        // Lakukan pembaruan timestamp terakhir dilihat pada catatan dengan ID tertentu
        $this->set(['timestamp' => date('Y-m-d H:i:s')])->where('id_catatan', $catatanId)->update();

        return $this->affectedRows() > 0; // Memberikan true jika terjadi pembaruan
    }


    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
