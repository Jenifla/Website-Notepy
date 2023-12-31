<?php

namespace App\Controllers;

// use App\Database\Migrations\Todolist;
use CodeIgniter\API\ResponseTrait;
use App\Models\TodolistModel;
use App\Models\TugasModel;
use App\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TugasController extends BaseController
{
    use ResponseTrait;

    // Fungsi untuk menampilkan daftar folder
    public function index()
    {
        $key = getenv('JWT_SECRET');
    $header = $this->request->getHeaderLine("Authorization");
    $token = null;

    // extract the token from the header
    if (!empty($header)) {
        if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
            $token = $matches[1];
        }
    }

    // check if token is null or empty
    if (is_null($token) || empty($token)) {
        return $this->respond(['error' => 'Access denied'], 401);
    }

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $userId = $decoded->iss; // ID pengguna dari token

        // Dapatkan data folder dari database berdasarkan ID pengguna
        $todoModel = new TodolistModel();
        $todo = $todoModel->where(['id_user' => $userId, 'dihapus' => 0])->findAll();

        return $this->respond($todo, 200);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
    }

    public function create($todoId = null)
    {
        $key = getenv('JWT_SECRET');
        $header = $this->request->getHeaderLine("Authorization");
        $token = null;
    
        // extract the token from the header
        if (!empty($header)) {
            if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
                $token = $matches[1];
            }
        }
    
        // check if token is null or empty
        if (is_null($token) || empty($token)) {
            return $this->respond(['error' => 'Access denied'], 401);
        }
    
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            
            $userId = $decoded->iss;
            
            // Dapatkan data todolist dari database
            $todoModel = new TodolistModel();
            $todo = $todoModel->find($todoId);
    
            if ($todo['id_user'] !== $userId) {
                return $this->failForbidden('You are not allowed to add tasks to this Todolist');
            }
    
            $tugas = $this->request->getVar('tugas');
    
            // Mengakses pengguna (id_user) melalui relasi dengan TodolistModel
            $user_id_from_todolist = $todo['id_user'];
    
            $tugasModel = new TugasModel();
            $newTugas = [
                'id_user' => $user_id_from_todolist,
                'id_todolist' => $todoId,
                'tugas' => $tugas,
                'status' => 0 // Tugas baru diatur sebagai belum selesai (0)
                // ... (Kolom lain yang diperlukan untuk membuat tugas)
            ];
    
            $tugasModel->insert($newTugas);
    
            return $this->respondCreated(['message' => 'Task created successfully']);
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }
    


    // Fungsi untuk memperbarui folder berdasarkan ID
    public function update($tugasId = null)
    {
        $key = getenv('JWT_SECRET');
        $header = $this->request->getHeaderLine("Authorization");
        $token = null;
        $userId = null;

        // extract the token from the header
        if (!empty($header)) {
            if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
                $token = $matches[1];
            }
        }

        // check if token is null or empty
        if (is_null($token) || empty($token)) {
            return $this->respond(['error' => 'Access denied'], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $iss = $decoded->iss;
        
            // Dapatkan data pengguna dari database
            $tugasModel = new TugasModel();
            $tugas = $tugasModel->find($tugasId); // Menggunakan find karena id_user unik

            if (!$tugas) {
                return $this->failNotFound('Task not found');
            }

            if ($tugas['id_user'] !== $userId) {
                return $this->failForbidden('You are not allowed to change the status of this task');
            }

            // Ubah status tugas menjadi selesai atau belum selesai
            $newStatus = ($tugas['status'] == 0) ? 1 : 0;
            $tugasModel->update($tugasId, ['status' => $newStatus]);

            return $this->respond(['message' => 'Task status updated successfully']);
    
            
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }
    
    // Fungsi untuk menghapus folder berdasarkan ID
    public function delete($tugasId = null)
    {
        $key = getenv('JWT_SECRET');
        $header = $this->request->getHeaderLine("Authorization");
        $token = null;

        // extract the token from the header
        if (!empty($header)) {
            if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
                $token = $matches[1];
            }
        }

        // check if token is null or empty
        if (is_null($token) || empty($token)) {
            return $this->respond(['error' => 'Access denied'], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $iss = $decoded->iss;
        
            // // Dapatkan data pengguna dari database
            // $userModel = new UserModel();
            // $userData = $userModel->find($iss);

            $tugasModel = new TugasModel();
            $tugas = $tugasModel->find($tugasId);

            if (!$tugas) {
                return $this->failNotFound('Note not found');
            }

            // if ($tugas['id_user'] !== $userData['id_user']) {
            //     return $this->failForbidden('You are not allowed to delete this note');
            // }

            $tugasModel->delete($tugasId);

            return $this->respondDeleted(['message' => 'Note deleted successfully']);
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }

}
