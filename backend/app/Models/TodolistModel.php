<?php

namespace App\Models;

use CodeIgniter\Model;

class TodolistModel extends Model
{
    protected $table            = 'todolist';
    protected $primaryKey       = 'id_todolist';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id_user', 'id_folder', 'judul_todolist',  'difavoritkan', 'dihapus', 'tgl_buat', 'tgl_edit', 'timestamp'];

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


    public function updateTaskStatus($taskId, $statusData)
    {
        try {
            $builder = $this->db->table('tugas'); // Ganti dengan nama tabel tugas

            // Lakukan pembaruan status tugas berdasarkan $taskId
            $builder->where('id_tugas', $taskId);
            $builder->update($statusData);

            return true; // Jika berhasil melakukan pembaruan status tugas
        } catch (\Exception $e) {
            // Tangani kesalahan jika pembaruan gagal
            return $e->getMessage();
        }
    }
}
