<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\CatatanModel;
use App\Models\UserModel;
use App\Models\FolderModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CatatanController extends BaseController
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
        $catatanModel = new CatatanModel();
        $catatan = $catatanModel->where(['id_user' => $userId, 'dihapus' => 0])->findAll();

        return $this->respond($catatan, 200);
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
            $catatanJudul = $this->request->getVar('judul');
            $catatanIsi = $this->request->getVar('isi');
            $createdAt = date('Y-m-d H:i:s');

            $catatanModel = new CatatanModel();
            $newCatatan = [
                'id_user' => $userData['id_user'], // Pastikan kolom fk_id_user sesuai dengan struktur tabel Anda
                'judul' => $catatanJudul,
                'isi' => $catatanIsi,
                'tgl_buat' => $createdAt
                // Kolom lain yang diperlukan untuk membuat folder baru
            ];

            $catatanModel->insert($newCatatan);

            return $this->respondCreated(['message' => 'Note created successfully']);
        } else {
            return $this->failNotFound('User not found');
        }
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized');
    }
}


    // Fungsi untuk memperbarui folder berdasarkan ID
    public function update($catatanId = null)
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
                $catatanModel = new CatatanModel();
                $catatan = $catatanModel->find($catatanId);
                if($catatan['id_user'] == $userData['id_user']){
                   
                    $newJudulCatatan = $this->request->getVar('judul');
                    $newIsiCatatan = $this->request->getVar('isi');
                    $updatedAt = date('Y-m-d H:i:s');

                    if (empty($newJudulCatatan) || empty($newIsiCatatan)) {
                        return $this->fail('New folder name is required', 400);
                    }
    
                    // Check apakah ada perubahan pada judul atau isi catatan
                    if ($catatan['judul'] !== $newJudulCatatan || $catatan['isi'] !== $newIsiCatatan) {
                        $updatedCatatanData = [
                            'judul' => $newJudulCatatan,
                            'isi' => $newIsiCatatan,
                            'tgl_edit' => $updatedAt // Menambahkan tanggal saat ini
                        ];
                    } else {
                        // Tidak ada perubahan, hanya kirim pesan bahwa tidak ada update
                        return $this->respond(['message' => 'No changes made to the note']);
                    }
    
                    $catatanModel->update($catatanId, $updatedCatatanData);
    
                    return $this->respond(['message' => 'Note updated successfully']);
                }
                 else {
                    return $this->failNotFound('Note not found');
                }

                

               
            } else {
                return $this->failNotFound('User not found');
            }
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }
    
    
    // Fungsi untuk menghapus folder berdasarkan ID
    public function trash($catatanId = null)
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

            $catatanModel = new CatatanModel();
            $catatan = $catatanModel->find($catatanId);

            if (!$catatan) {
                return $this->failNotFound('Note not found');
            }

            if ($catatan['id_user'] !== $userData['id_user']) {
                return $this->failForbidden('You are not allowed to delete this note');
            }

            $catatanModel->set('dihapus', 1)->where('id_catatan', $catatanId)->update();

            return $this->respondDeleted(['message' => 'Note deleted successfully']);
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }

    public function getTrash()
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
        $catatanModel = new CatatanModel();
        $catatan = $catatanModel->where(['id_user' => $userId, 'dihapus' => 1])->findAll();

        return $this->respond($catatan, 200);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
    }

    // Fungsi untuk menghapus folder berdasarkan ID
    public function delete($catatanId = null)
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

            $catatanModel = new CatatanModel();
            $catatan = $catatanModel->find($catatanId);

            if (!$catatan) {
                return $this->failNotFound('Note not found');
            }

            if ($catatan['id_user'] !== $userData['id_user']) {
                return $this->failForbidden('You are not allowed to delete this note');
            }

            $catatanModel->delete($catatanId);

            return $this->respondDeleted(['message' => 'Note deleted successfully']);
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }

    public function restore($catatanId = null)
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

        $catatanModel = new CatatanModel();
        $catatan = $catatanModel->find($catatanId);

        if (!$catatan) {
            return $this->failNotFound('Note not found');
        }

        if ($catatan['id_user'] !== $userData['id_user']) {
            return $this->failForbidden('You are not allowed to restore this note');
        }

        // Periksa apakah catatan sudah dalam status terhapus
        if ($catatan['dihapus'] == 0) {
            return $this->respond(['message' => 'Note is already restored']);
        }

        // Ubah status penghapusan kembali menjadi 0 (tidak terhapus)
        $updatedCatatanData = [
            'dihapus' => 0
        ];

        $catatanModel->update($catatanId, $updatedCatatanData);

        return $this->respond(['message' => 'Note restored successfully']);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}


// ...

// Fungsi untuk memfavoritkan catatan berdasarkan ID
public function favorite($catatanId = null)
{
    $key = getenv('JWT_SECRET');
    $header = $this->request->getHeaderLine("Authorization");
    $token = null;

    // Ekstrak token dari header
    if (!empty($header)) {
        if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
            $token = $matches[1];
        }
    }

    // Periksa jika token kosong atau tidak ada
    if (is_null($token) || empty($token)) {
        return $this->respond(['error' => 'Access denied'], 401);
    }

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $iss = $decoded->iss;

        // Dapatkan data pengguna dari database
        $userModel = new UserModel();
        $userData = $userModel->find($iss);

        $catatanModel = new CatatanModel();
        $catatan = $catatanModel->find($catatanId);

        if (!$catatan) {
            return $this->failNotFound('Note not found');
        }

        if ($catatan['id_user'] !== $userData['id_user']) {
            return $this->failForbidden('You are not allowed to favorite this note');
        }

        // Toggle status 'difavoritkan' catatan
        $favorited = !$catatan['difavoritkan'];

        $updatedCatatanData = [
            'difavoritkan' => $favorited ? 1 : 0 // Mengubah status favorit berdasarkan kondisi toggle
            // Anda juga bisa menambahkan kolom lain yang ingin diubah di sini jika diperlukan
        ];

        $catatanModel->update($catatanId, $updatedCatatanData);

        // Ambil data terbaru catatan setelah diperbarui
        $updatedCatatan = $catatanModel->find($catatanId);

        $message = $favorited ? 'Note favorited successfully' : 'Note unfavorited successfully';

        // Kembalikan respons dengan pesan dan data terbaru catatan yang diperbarui
        return $this->respond([
            'message' => $message,
            'updatedCatatan' => $updatedCatatan // Kirim data terbaru catatan yang diperbarui ke frontend
        ]);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}

public function getFavorite()
{
    $key = getenv('JWT_SECRET');
    $header = $this->request->getHeaderLine("Authorization");
    $token = null;

    // Ekstrak token dari header
    if (!empty($header)) {
        if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
            $token = $matches[1];
        }
    }

    // Periksa jika token kosong atau tidak ada
    if (is_null($token) || empty($token)) {
        return $this->respond(['error' => 'Access denied'], 401);
    }

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $userId = $decoded->iss;

        // Dapatkan catatan yang difavoritkan dari database berdasarkan ID pengguna
        $catatanModel = new CatatanModel();
        $favoriteNotes = $catatanModel->where(['id_user' => $userId, 'difavoritkan' => 1])->findAll();

        return $this->respond($favoriteNotes, 200);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}

// Fungsi untuk mengkategorikan catatan ke dalam folder
public function kategori($catatanId = null)
{
    $key = getenv('JWT_SECRET');
    $header = $this->request->getHeaderLine("Authorization");
    $token = null;

    // Ekstrak token dari header
    if (!empty($header)) {
        if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
            $token = $matches[1];
        }
    }

    // Periksa jika token kosong atau tidak ada
    if (is_null($token) || empty($token)) {
        return $this->respond(['error' => 'Access denied'], 401);
    }

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $iss = $decoded->iss;

        // Dapatkan data pengguna dari database
        $userModel = new UserModel();
        $userData = $userModel->find($iss);

        if ($userData) {
        $catatanModel = new CatatanModel();
        $catatan = $catatanModel->find($catatanId);

        // if (!$catatan) {
        //     return $this->failNotFound('Note not found');
        // }

        // Periksa jika pengguna memiliki akses ke catatan tersebut
        if ($catatan['id_user'] == $userData['id_user']) {
            $folderId = $this->request->getVar('folderId');

            if (!$folderId) {
                return $this->fail('Folder ID is required', 400);
            }

            // Dapatkan data folder dari database berdasarkan ID folder
            $folderModel = new FolderModel(); // Ganti dengan model folder yang sesuai
            $folder = $folderModel->find($folderId);

            if (!$folder) {
                return $this->failNotFound('Folder not found');
            }

        // Lakukan kategori catatan ke dalam folder
        $updatedCatatanData = [
            'id_folder' => $folderId // Simpan ID folder ke dalam catatan
            // Anda juga bisa menambahkan kolom lain yang ingin diubah di sini jika diperlukan
        ];

        $catatanModel->update($catatanId, $updatedCatatanData);

        // Ambil data terbaru catatan setelah diperbarui
        $updatedCatatan = $catatanModel->find($catatanId);
        // Dapatkan nama folder yang dipilih
        $selectedFolder = $folderModel->find($folderId);
        $folderName = $selectedFolder['nama_folder']; // Ganti 'nama_folder' dengan nama kolom yang sesuai

        // Kembalikan respons dengan pesan dan data terbaru catatan yang diperbarui
        return $this->respond([
            'message' => 'Note categorized successfully',
            'updatedCatatan' => $updatedCatatan, // Kirim data terbaru catatan yang diperbarui ke frontend
            'selectedFolderName' => $folderName // Kirim nama folder yang dipilih ke frontend
        ]); 
        }

        
    }
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}

// Fungsi untuk menghapus kategori dari catatan
public function unkategori($catatanId = null)
{
    $key = getenv('JWT_SECRET');
    $header = $this->request->getHeaderLine("Authorization");
    $token = null;

    // Ekstrak token dari header
    if (!empty($header)) {
        if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
            $token = $matches[1];
        }
    }

    // Periksa jika token kosong atau tidak ada
    if (is_null($token) || empty($token)) {
        return $this->respond(['error' => 'Access denied'], 401);
    }

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $iss = $decoded->iss;

        // Dapatkan data pengguna dari database
        $userModel = new UserModel();
        $userData = $userModel->find($iss);

        if ($userData) {
            $catatanModel = new CatatanModel();
            $catatan = $catatanModel->find($catatanId);

            // Periksa jika pengguna memiliki akses ke catatan tersebut
            if ($catatan['id_user'] == $userData['id_user']) {
                // Hapus kategori dari catatan (Set id_folder menjadi null)
                $updatedCatatanData = [
                    'id_folder' => null // Hapus ID folder dari catatan
                    // Anda juga bisa menambahkan kolom lain yang ingin diubah di sini jika diperlukan
                ];

                $catatanModel->update($catatanId, $updatedCatatanData);

                // Ambil data terbaru catatan setelah diperbarui
                $updatedCatatan = $catatanModel->find($catatanId);

                // Kembalikan respons dengan pesan dan data terbaru catatan yang diperbarui
                return $this->respond([
                    'message' => 'Note uncategorized successfully',
                    'updatedCatatan' => $updatedCatatan, // Kirim data terbaru catatan yang diperbarui ke frontend
                    'selectedFolderName' => null
                ]);
            }
        }
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}



public function getByFolder($folderId = null)
{
    $key = getenv('JWT_SECRET');
    $header = $this->request->getHeaderLine("Authorization");
    $token = null;

    // Ekstrak token dari header
    if (!empty($header)) {
        if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
            $token = $matches[1];
        }
    }

    // Periksa jika token kosong atau tidak ada
    if (is_null($token) || empty($token)) {
        return $this->respond(['error' => 'Access denied'], 401);
    }

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $userId = $decoded->iss;

        if ($folderId === null) {
            return $this->fail('Folder ID is required', 400);
        }

        // Dapatkan catatan dari database berdasarkan ID folder yang sesuai dengan ID pengguna
        $catatanModel = new CatatanModel();
        $folderNotes = $catatanModel->where('id_folder', $folderId)
                                ->where('id_user', $userId) // Sesuaikan dengan kolom ID pengguna pada tabel catatan
                                ->where('dihapus', 0) // Hanya catatan yang tidak dihapus
                                ->findAll();

       
            return $this->respond($folderNotes, 200);
        
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}

public function riwayat($catatanId)
    {
        $key = getenv('JWT_SECRET');
    $header = $this->request->getHeaderLine("Authorization");
    $token = null;

    // Ekstrak token dari header
    if (!empty($header)) {
        if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
            $token = $matches[1];
        }
    }

    // Periksa jika token kosong atau tidak ada
    if (is_null($token) || empty($token)) {
        return $this->respond(['error' => 'Access denied'], 401);
    }

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $iss = $decoded->iss;

        // Dapatkan data pengguna dari database
        $userModel = new UserModel();
        $userData = $userModel->find($iss);

        if ($userData) {
            $catatanModel = new CatatanModel(); // Ganti dengan model yang sesuai
        
            // Lakukan pembaruan timestamp terakhir dilihat pada catatan dengan ID tertentu
            $updated = $catatanModel->updateLastViewedAt($catatanId);

            if ($updated) {
                return $this->respond(['message' => 'Last viewed timestamp updated successfully']);
            } else {
                return $this->failServerError('Failed to update last viewed timestamp');
            }
        }


    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }

    }

}
