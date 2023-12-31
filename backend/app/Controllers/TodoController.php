<?php

namespace App\Controllers;

// use App\Database\Migrations\Todolist;

use App\Database\Migrations\Todolist;
use CodeIgniter\API\ResponseTrait;
use App\Models\TodolistModel;
use App\Models\TugasModel;
use App\Models\UserModel;
use App\Models\FolderModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TodoController extends BaseController
{
    use ResponseTrait;

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
    
            $todoList = [];
        
        // Ambil data todolist dari database
        $todoModel = new TodolistModel();
        $todolists = $todoModel->where('id_user', $userId)->where('dihapus', 0)->findAll();  // Ambil semua todolist dari database
        
        // Loop melalui setiap todolist
        foreach ($todolists as $list) {
            $todoItem = [
                'id_todolist' => $list['id_todolist'],
                'judul_todolist' => $list['judul_todolist'],
                'date' => $list['tgl_buat'],
                'tugas' => [] // Inisialisasi array untuk menampung data tugas
            ];
            
            // Ambil tugas-tugas terkait dengan todolist saat ini
            $taskModel = new TugasModel();
            $tasks = $taskModel->where('id_todolist', $list['id_todolist'])->findAll();
            
            // Loop melalui setiap tugas dan tambahkan ke todolist saat ini
            foreach ($tasks as $task) {
                $todoTask = [
                    'id_tugas' => $task['id_tugas'],
                    'tugas' => $task['tugas'],
                    'completed' => $task['status'] == 1 // Misal status 1 menandakan sudah selesai
                ];
                
                // Tambahkan tugas ke dalam todolist saat ini
                $todoItem['tugas'][] = $todoTask;
            }
            
            // Tambahkan todolist saat ini ke dalam array todoList
            $todoList[] = $todoItem;
        }
        
        // Kirim respons dengan data todolist yang telah diolah
        return $this->respond($todoList, 200);
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }
    

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
    
        $db = db_connect();
        $db->transBegin(); // Start a transaction manually
    
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $iss = $decoded->iss;
    
            // Dapatkan data pengguna dari database
            $userModel = new UserModel();
            $userData = $userModel->find($iss); // Menggunakan find karena id_user unik
    
            if ($userData) {
                $todoModel = new TodolistModel();
                $jsonInput = $this->request->getJSON();
                $judul_todolist = $jsonInput->judul_todolist;
                $tugas = $jsonInput->tugas;
                $createdAt = date('Y-m-d H:i:s');
                $newTodo = [
                    'id_user' => $userData['id_user'],
                    'judul_todolist' => $judul_todolist,
                    'tgl_buat' => $createdAt
                    // Kolom lain yang diperlukan untuk membuat folder baru
                ];
    
                $todoId = $todoModel->insert($newTodo);
    
                if ($tugas && is_array($tugas)) {
                    foreach ($tugas as $tugasItem) {
                        
                    
                        $newTugas = [
                            'id_todolist' => $todoId,
                            'tugas' => $tugasItem->tugas ?? null,
                            'status' => $tugasItem->status ?? false,
                            // Tambahkan kolom lain yang diperlukan untuk membuat tugas baru
                        ];

                         // Tambahkan log untuk memantau nilai $newTugas
                        error_log('Data newTugas: ' . print_r($newTugas, true));
                        $db->table('tugas')->insert($newTugas);
                        error_log('Query executed for newTugas');
                    }
                }
    
                $db->transCommit(); // Commit the transaction if all queries executed successfully
                return $this->respondCreated(['message' => 'Note created successfully']);
            } else {
                return $this->failNotFound('User not found');
            }
        } catch (\Exception $e) {
            $db->transRollback(); // Rollback the transaction if an exception occurs
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }
    


    public function update($todoId = null)
{
    $key = getenv('JWT_SECRET');
    $header = $this->request->getHeaderLine("Authorization");
    $token = null;

    // Extract the token from the header
    if (!empty($header)) {
        if (preg_match('/Bearer\s+(.*)$/', $header, $matches)) {
            $token = $matches[1];
        }
    }

    // Check if the token is null or empty
    if (is_null($token) || empty($token)) {
        return $this->respond(['error' => 'Access denied'], 401);
    }

    $db = db_connect();
    $db->transBegin(); // Start a transaction manually

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $iss = $decoded->iss;

        // Get user data from the database
        $userModel = new UserModel();
        $userData = $userModel->find($iss); // Using find because id_user is unique

        if ($userData) {
            $todoModel = new TodolistModel();
            

            $existingTodo = $todoModel->find($todoId);

            if ($existingTodo['id_user'] === $userData['id_user']) {
                if ($existingTodo) {
                    $jsonInput = $this->request->getJSON();
                    $judul_todolist = $jsonInput->judul_todolist;
                    $tugas = $jsonInput->tugas;
                    $updatedAt = date('Y-m-d H:i:s');
                    // Perform the necessary updates based on the received data

                    // Cek apakah judul yang diterima sama dengan yang ada di database
                    if ($judul_todolist !== $existingTodo['judul_todolist']) {
                        // Jika tidak sama, lakukan pembaruan judul
                        $updatedTodo = [
                            'judul_todolist' => $judul_todolist,
                            'tgl_edit' => $updatedAt
                        ];
                        $todoModel->update($todoId, $updatedTodo);
                    }

                    // Check if there's a change in the tasks' status
                    if ($tugas && is_array($tugas)) {
                        foreach ($tugas as $tugasItem) {
                            $statusData = [
                                'id_todolist' => $todoId,
                                'status' => $tugasItem->status ?? false
                                // Add other columns needed for creating new tasks
                            ];
                            $taskId = $tugasItem->id_tugas ?? null;

                            if ($taskId) {
                                // Update the task status if the task ID exists
                                $todoModel->updateTaskStatus($taskId, $statusData);
                            }
                        }
                    }

                    $db->transCommit(); // Commit the transaction if all queries executed successfully
                    return $this->respondUpdated(['message' => 'Note updated successfully']);
                } else {
                    return $this->failNotFound('Note not found');
                }
            } else {
                return $this->failForbidden('You are not allowed to update this note');
            }
        } else {
            return $this->failNotFound('User not found');
        }
    } catch (\Exception $e) {
        $db->transRollback(); // Rollback the transaction if an exception occurs
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}


    
    
    
    // Fungsi untuk menghapus folder berdasarkan ID
    public function trash($todoId = null)
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

            $todoModel = new TodolistModel();
            $todo = $todoModel->find($todoId);

            if (!$todo) {
                return $this->failNotFound('Note not found');
            }

            if ($todo['id_user'] !== $userData['id_user']) {
                return $this->failForbidden('You are not allowed to delete this note');
            }

            $todoModel->set('dihapus', 1)->where('id_todolist', $todoId)->update();

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
        $todoList = [];
        // Dapatkan data folder dari database berdasarkan ID pengguna
        $todoModel = new TodolistModel();
        $todo = $todoModel->where(['id_user' => $userId, 'dihapus' => 1])->findAll();

        // Loop melalui setiap todolist
        foreach ($todo as $list) {
            $todoItem = [
                'id_todolist' => $list['id_todolist'],
                'judul_todolist' => $list['judul_todolist'],
                'date' => $list['tgl_buat'],
                'tugas' => [] // Inisialisasi array untuk menampung data tugas
            ];
            
            // Ambil tugas-tugas terkait dengan todolist saat ini
            $taskModel = new TugasModel();
            $tasks = $taskModel->where('id_todolist', $list['id_todolist'])->findAll();
            
            // Loop melalui setiap tugas dan tambahkan ke todolist saat ini
            foreach ($tasks as $task) {
                $todoTask = [
                    'id_tugas' => $task['id_tugas'],
                    'tugas' => $task['tugas'],
                    'completed' => $task['status'] == 1 // Misal status 1 menandakan sudah selesai
                ];
                
                // Tambahkan tugas ke dalam todolist saat ini
                $todoItem['tugas'][] = $todoTask;
            }
            
            // Tambahkan todolist saat ini ke dalam array todoList
            $todoList[] = $todoItem;
        }

        return $this->respond($todoList, 200);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
    }

    // Fungsi untuk menghapus folder berdasarkan ID
    public function delete($todoId = null)
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
            

            $todoModel = new TodolistModel();
            $todo = $todoModel->find($todoId);

            if (!$todo) {
                return $this->failNotFound('Note not found');
            }

            if ($todo['id_user'] !== $userData['id_user']) {
                return $this->failForbidden('You are not allowed to delete this note');
            }

                // Hapus terlebih dahulu entri terkait dari tabel tugas
            $taskModel = new TugasModel();
            $taskModel->where('id_todolist', $todoId)->delete();
            
            $todoModel->delete($todoId);

            return $this->respondDeleted(['message' => 'Note deleted successfully']);
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }

    public function restore($todoId = null)
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

        $todoModel = new TodolistModel();
        $todo = $todoModel->find($todoId);

        if (!$todo) {
            return $this->failNotFound('Note not found');
        }

        if ($todo['id_user'] !== $userData['id_user']) {
            return $this->failForbidden('You are not allowed to restore this note');
        }

        // Periksa apakah catatan sudah dalam status terhapus
        if ($todo['dihapus'] == 0) {
            return $this->respond(['message' => 'Note is already restored']);
        }

        // Ubah status penghapusan kembali menjadi 0 (tidak terhapus)
        $updatedTodolistData = [
            'dihapus' => 0
        ];

        $todoModel->update($todoId, $updatedTodolistData);

        return $this->respond(['message' => 'Note restored successfully']);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}


// Fungsi untuk memfavoritkan catatan berdasarkan ID
public function favorite($todoId = null)
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

        $todoModel = new TodolistModel();
        $todo = $todoModel->find($todoId);

        if (!$todo) {
            return $this->failNotFound('Note not found');
        }

        if ($todo['id_user'] !== $userData['id_user']) {
            return $this->failForbidden('You are not allowed to favorite this note');
        }

        // Toggle status 'difavoritkan' catatan
        $favorited = !$todo['difavoritkan'];

        $updatedCatatanData = [
            'difavoritkan' => $favorited ? 1 : 0 // Mengubah status favorit berdasarkan kondisi toggle
            // Anda juga bisa menambahkan kolom lain yang ingin diubah di sini jika diperlukan
        ];

        $todoModel->update($todoId, $updatedCatatanData);

        // Ambil data terbaru catatan setelah diperbarui
        $updatedCatatan = $todoModel->find($todoId);

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

        $todoList = [];
    
    // Ambil data todolist dari database
    $todoModel = new TodolistModel();
    $todolists = $todoModel
    ->where('id_user', $userId)
    ->where('dihapus', 0)
    ->where('difavoritkan', 1)
    ->findAll();
    
    // Loop melalui setiap todolist
    foreach ($todolists as $list) {
        $todoItem = [
            'id_todolist' => $list['id_todolist'],
            'judul_todolist' => $list['judul_todolist'],
            'difavoritkan' => $list['difavoritkan'],
            'date' => $list['tgl_buat'],
            'tugas' => [] // Inisialisasi array untuk menampung data tugas
        ];
        
        // Ambil tugas-tugas terkait dengan todolist saat ini
        $taskModel = new TugasModel();
        $tasks = $taskModel->where('id_todolist', $list['id_todolist'])->findAll();
        
        // Loop melalui setiap tugas dan tambahkan ke todolist saat ini
        foreach ($tasks as $task) {
            $todoTask = [
                'id_tugas' => $task['id_tugas'],
                'tugas' => $task['tugas'],
                'completed' => $task['status'] == 1 // Misal status 1 menandakan sudah selesai
            ];
            
            // Tambahkan tugas ke dalam todolist saat ini
            $todoItem['tugas'][] = $todoTask;
        }
        
        // Tambahkan todolist saat ini ke dalam array todoList
        $todoList[] = $todoItem;
    }
    
    // Kirim respons dengan data todolist yang telah diolah
    return $this->respond($todoList, 200);
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}

public function kategori($todoId = null)
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

        // // Dapatkan data pengguna dari database
        // $userModel = new UserModel();
        
           // Dapatkan data todolist berdasarkan id_user
        $todoModel = new TodolistModel();
        $todo = $todoModel->where('id_user', $userId)
                          ->where('id_todolist', $todoId)
                          ->first();
            var_dump(($todo));
            // Cek apakah $todoId ada dalam daftar todolist yang ditemukan
            if ($todo) { 
                    
                    var_dump($todo);
                    // Dapatkan ID folder dari body atau query string (sesuai dengan kebutuhan)
                    $folderId = $this->request->getVar('folderId'); // Misalnya, jika ID folder ada di body
                    // $folderId = $this->request->getVar('folderId'); // Gunakan ini jika ID folder ada di query string

                    if (!$folderId) {
                        return $this->fail('Folder ID is required', 400);
                    }
                    // Dapatkan data folder dari database
                    $folderModel = new FolderModel();
                    $selectedFolder = $folderModel->find($folderId);

                    if (!$selectedFolder) {
                        return $this->failNotFound('Folder not found');
                    }

                    // Lakukan proses kategorisasi todolist ke dalam folder yang dipilih
                    $updatedTodoData = [
                        'id_folder' => $folderId // Perbarui ID folder pada todolist
                        // Anda bisa menambahkan kolom lain yang ingin diubah di sini jika diperlukan
                    ];

                    $todoModel->update($todoId, $updatedTodoData);

                     // Ambil data terbaru catatan setelah diperbarui
                    $updatedTodolist = $todoModel->find($todoId);
                    // Dapatkan nama folder yang dipilih
                    $selectedFolder = $folderModel->find($folderId);
                    $folderName = $selectedFolder['nama_folder'];
                     // Commit transaksi jika semua query dieksekusi dengan sukses
                    return $this->respond([
                        'message' => 'Todolist categorized successfully',
                        'updatedTodolist' => $updatedTodolist, // Kirim data terbaru catatan yang diperbarui ke frontend
                        'selectedFolderName' => $folderName
                    ]);
                   
                }
                else {
                    return $this->failNotFound('Todolist not found');
                }
        
    } catch (\Exception $e) {// Rollback transaksi jika terjadi kesalahan
        return $this->failUnauthorized('Unauthorized: ' . $e->getMessage());
    }
}

public function unkategori($todoId = null)
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

        // // Dapatkan data pengguna dari database
        // $userModel = new UserModel();
        
           // Dapatkan data todolist berdasarkan id_user
        $todoModel = new TodolistModel();
        $todo = $todoModel->where('id_user', $userId)
                          ->where('id_todolist', $todoId)
                          ->first();
            var_dump(($todo));
            // Cek apakah $todoId ada dalam daftar todolist yang ditemukan
            if ($todo) { 
                    
                    var_dump($todo);
                    // // Dapatkan ID folder dari body atau query string (sesuai dengan kebutuhan)
                    // $folderId = $this->request->getVar('folderId'); // Misalnya, jika ID folder ada di body
                    // // $folderId = $this->request->getVar('folderId'); // Gunakan ini jika ID folder ada di query string

                    // Dapatkan data folder dari database
                    $folderModel = new FolderModel();
                    // $selectedFolder = $folderModel->find($folderId);

                    // if (!$selectedFolder) {
                    //     return $this->failNotFound('Folder not found');
                    // }

                    // Lakukan proses kategorisasi todolist ke dalam folder yang dipilih
                    $updatedTodoData = [
                        'id_folder' => null // Perbarui ID folder pada todolist
                        // Anda bisa menambahkan kolom lain yang ingin diubah di sini jika diperlukan
                    ];

                    $todoModel->update($todoId, $updatedTodoData);

                     // Ambil data terbaru catatan setelah diperbarui
                    $updatedTodolist = $todoModel->find($todoId);
                    // Dapatkan nama folder yang dipilih
                    // $selectedFolder = $folderModel->find($folderId);
                    // $folderName = $selectedFolder['nama_folder'];
                     // Commit transaksi jika semua query dieksekusi dengan sukses
                    return $this->respond([
                        'message' => 'Todolist uncategorized successfully',
                        'updatedTodolist' => $updatedTodolist, // Kirim data terbaru catatan yang diperbarui ke frontend
                        'selectedFolderName' => null
                    ]);
                   
                }
                else {
                    return $this->failNotFound('Todolist not found');
                }
        
    } catch (\Exception $e) {// Rollback transaksi jika terjadi kesalahan
        return $this->failUnauthorized('Unauthorized: ' . $e->getMessage());
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

        $todoList = [];
    

        if ($folderId === null) {
            return $this->fail('Folder ID is required', 400);
        }

        // Dapatkan catatan dari database berdasarkan ID folder yang sesuai dengan ID pengguna
        $todoModel = new TodolistModel();
        $folderNotes = $todoModel->where('id_folder', $folderId)
                                ->where('id_user', $userId) // Sesuaikan dengan kolom ID pengguna pada tabel catatan
                                ->where('dihapus', 0) // Hanya catatan yang tidak dihapus
                                ->findAll();

        // Loop melalui setiap todolist
        foreach ($folderNotes as $list) {
            $todoItem = [
                'id_todolist' => $list['id_todolist'],
                'judul_todolist' => $list['judul_todolist'],
                'difavoritkan' => $list['difavoritkan'],
                'date' => $list['tgl_buat'],
                'tugas' => [] // Inisialisasi array untuk menampung data tugas
            ];
        
        // Ambil tugas-tugas terkait dengan todolist saat ini
        $taskModel = new TugasModel();
        $tasks = $taskModel->where('id_todolist', $list['id_todolist'])->findAll();
        
        // Loop melalui setiap tugas dan tambahkan ke todolist saat ini
        foreach ($tasks as $task) {
            $todoTask = [
                'id_tugas' => $task['id_tugas'],
                'tugas' => $task['tugas'],
                'completed' => $task['status'] == 1 // Misal status 1 menandakan sudah selesai
            ];
            
            // Tambahkan tugas ke dalam todolist saat ini
            $todoItem['tugas'][] = $todoTask;
        }

        $todoList[] = $todoItem;
    }
        

       
            return $this->respond($todoList, 200);
        
    } catch (\Exception $e) {
        return $this->failUnauthorized('Unauthorized' . $e->getMessage());
    }
}


}
