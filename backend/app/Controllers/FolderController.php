<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\FolderModel;
use App\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FolderController extends BaseController
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
        $folderModel = new FolderModel();
        $folders = $folderModel->where('id_user', $userId)->findAll();

        return $this->respond($folders, 200);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
    }

    // Fungsi untuk membuat folder baru
    public function create()
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
        
        // Dapatkan data pengguna dari database
        $userModel = new UserModel();
        $userData = $userModel->find($iss); // Menggunakan find karena id_user unik

        if ($userData) {
            $folderName = $this->request->getVar('nama_folder');

            if (empty($folderName)) {
                return $this->fail('Folder name is required', 400);
            }

            $folderModel = new FolderModel();
            $newFolder = [
                'id_user' => $userData['id_user'], // Pastikan kolom fk_id_user sesuai dengan struktur tabel Anda
                'nama_folder' => $folderName,
                // Kolom lain yang diperlukan untuk membuat folder baru
            ];

            $folderModel->insert($newFolder);

            return $this->respondCreated(['message' => 'Folder created successfully']);
        } else {
            return $this->failNotFound('User not found');
        }
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized');
    }
}


    // Fungsi untuk memperbarui folder berdasarkan ID
    public function update($folderId = null)
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
        
            // Dapatkan data pengguna dari database
            $userModel = new UserModel();
            $userData = $userModel->find($iss); // Menggunakan find karena id_user unik
    
            // if ($folder['id_user'] !== $userData['id_user']) {
            //     return $this->failForbidden('You are not allowed to update this folder');
            // }
           
            if ($userData) {
                $folderModel = new FolderModel();
                $folder = $folderModel->find($folderId);
                if($folder['id_user'] == $userData['id_user']){
                   
                    $newFolderName = $this->request->getVar('nama_folder');

                    if (empty($newFolderName)) {
                        return $this->fail('New folder name is required', 400);
                    }
    
                    $updatedFolderData = [
                        'nama_folder' => $newFolderName,
                    ];
    
                    $folderModel->update($folderId, $updatedFolderData);
    
                    return $this->respond(['message' => 'Folder updated successfully']);
                }
                 else {
                    return $this->failNotFound('Folder not found');
                }

                

               
            } else {
                return $this->failNotFound('User not found');
            }
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }

    // Fungsi untuk menghapus folder berdasarkan ID
    public function delete($folderId = null)
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
        
            // Dapatkan data pengguna dari database
            $userModel = new UserModel();
            $userData = $userModel->find($iss);

            $folderModel = new FolderModel();
            $folder = $folderModel->find($folderId);

            if (!$folder) {
                return $this->failNotFound('Folder not found');
            }

            if ($folder['id_user'] !== $userData['id_user']) {
                return $this->failForbidden('You are not allowed to delete this folder');
            }

            $folderModel->delete($folderId);

            return $this->respondDeleted(['message' => 'Folder deleted successfully']);
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }
}
