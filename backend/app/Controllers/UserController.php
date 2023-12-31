<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController extends ResourceController
{
    use ResponseTrait;

    public function signUp()
    {
        $model = new UserModel();

        $validation = $this->validate([
            'username' => 'required|alpha_numeric|min_length[3]|max_length[30]|is_unique[user.username]',
            'email' => 'required|valid_email|is_unique[user.email]',
            'password' => 'required|min_length[8]',
        ]);


        if (!$validation) {
            $this->response->setStatusCode(400);
            return $this->response->setJSON(
                [
                    'code' => 400,
                    'status' => 'BAD REQUEST',
                    'data' => null
                ]
            );
        }

        $data = [
            'username' => $this->request->getVar('username'),
            'email' => $this->request->getVar('email'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'Tgl_registrasi' => date('Y-m-d H:i:s')
        ];

        if ($model->save($data)) {
            $response = [
                'status' => 201,
                'error' => null,
                'messages' => [
                    'success' => 'Data Inserted'
                ]
            ];

            return $this->respondCreated($response);
        } else {
            return $this->fail($model->errors());
        }
    }



    public function updateProfil()
    {
        
        $key = getenv('JWT_SECRET');
        $header = $this->request->getHeaderLine("Authorization");
        $token = null;
    
        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
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

                // Extract data from the request
                $username = $this->request->getVar('username');
                $email = $this->request->getVar('email');
                $password = password_hash($this->request->getVar('password'), PASSWORD_DEFAULT);
                $status = $this->request->getVar('status');
                $lokasi = $this->request->getVar('lokasi');
                $no_tlpn = $this->request->getVar('no_tlpn');
                $foto_profil = $this->request->getFile('foto_profil');

                // Cek setiap data yang ingin diperbarui
                // Jika input kosong, gunakan data yang sudah tersimpan di database
                $data['username'] = !empty($username) ? $username : $userData['username'];
                $data['email'] = !empty($email) ? $email : $userData['email'];
                $data['password'] = !empty($password) ? $password : $userData['password'];
                $data['status'] = !empty($status) ? $status : $userData['status'];
                $data['lokasi'] = !empty($lokasi) ? $lokasi : $userData['lokasi'];
                $data['no_tlpn'] = !empty($no_tlpn) ? $no_tlpn : $userData['no_tlpn'];
                
                // Handle file upload jika ada file yang dikirim
                if ($foto_profil && $foto_profil->isValid() && !$foto_profil->hasMoved()) {
                    $newName = $foto_profil->getRandomName();
                    $foto_profil->move('./path', $newName);
                    $data['foto_profil'] = $newName;
                }
    
                $proses = $userModel->update($iss, $data);
    
                if ($proses) {
                    $response = [
                        'status' => 200,
                        'messages' => 'Data berhasil diubah',
                        'data' => $data,
                    ];
                } else {
                    $response = [
                        'status' => 402,
                        'messages' => 'Gagal diubah',
                    ];
                }
    
                return $this->respond($response);
            }
            else {
                return $this->failNotFound('User not found');
            }
        } catch (\Exception $e) {
            return $this->failUnauthorized('Unauthorized' . $e->getMessage());
        }
    }
    


















































    

     public function login()
    {
        $model = new UserModel();

        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $user = $model->where('email', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            session()->set('logged_in', true);
            session()->set('user_id', $user['id_user']);
            $response = [
                'status' => 200,
                'error' => null,
                'messages' => [
                    'success' => 'Login successful'
                ],
                'user' => $user
            ];

            return $this->respond($response);
        } else {
            $response = [
                'status' => 401,
                'error' => 'Login failed',
                'messages' => [
                    'error' => 'Login failed'
                ]
            ];

            return $this->respond($response);
        }
    }  


    public function getUser()
    {
        $model = new UserModel();
        $users = $model->findAll();

        return $this->respond($users);
    }




    public function show($id_user = null)
    {
        $model = new UserModel();
        $user = $model->find($id_user);

        if ($user) {
            return $this->respond($user);
        } else {
            $response = [
                'status' => 404,
                'error' => 'User not found',
                'messages' => [
                    'error' => 'User not found'
                ]
            ];

            return $this->respond($response);
        }
    }

   

    public function edit($id_user = null)
    {
        $model = new UserModel();
        $user = $model->find($id_user);

        if ($user) {
            return $this->respond($user);
        } else {
            $response = [
                'status' => 404,
                'error' => 'User not found',
                'messages' => [
                    'error' => 'User not found'
                ]
            ];

            return $this->respond($response);
        }
    }

    // function untuk mengedit data
    public function update($id_user = null)
    {
        $model = new UserModel();
        $users = $model->find($id_user);
        if ($users) {
            $data = [
                'id' => $id_user,
                'username' => $this->request->getVar('username') ?? $users['username'],
                'email' => $this->request->getVar('email') ?? $users['email'],

            ];
            $hashPasswordFromDatabase = $users['password'];
            $inputPassword = $this->request->getVar('password');

            if (!empty($inputPassword) && $inputPassword !== $hashPasswordFromDatabase) {
                // Jika password baru dimasukkan dan berbeda dengan password yang ada, hash password baru
                $data['password'] = password_hash($inputPassword, PASSWORD_DEFAULT);
            } else {
                // Jika tidak ada perubahan pada password, gunakan password yang ada
                $data['password'] = $hashPasswordFromDatabase;
            }

            $proses = $model->save($data);
            if ($proses) {
                $response = [
                    'status' => 200,
                    'messages' => 'Data berhasil diubah',
                    'data' => $data
                ];
            } else {
                $response = [
                    'status' => 402,
                    'messages' => 'Gagal diubah',
                ];
            }
            return $this->respond($response);
        }
        return $this->failNotFound('Data tidak ditemukan');
    }

    // function untuk menghapus data
    public function delete($id_user = null)
    {
        $userModel = new UserModel();
        $user = $userModel->find($id_user);
        if ($user) {
            $proses = $userModel->delete($id_user);
            if ($proses) {
                $response = [
                    'status' => 200,
                    'messages' => 'Data berhasil dihapus',
                ];
            } else {
                $response = [
                    'status' => 402,
                    'messages' => 'Gagal menghapus data',
                ];
            }
            return $this->respond($response);
        } else {
            return $this->failNotFound('Data tidak ditemukan');
        }
    }
}
