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
    // public function getUserData()
    // {
    //     // Ambil token dari header Authorization
    //     $token = $this->request->getHeaderLine('Authorization');

    //     // Pastikan bahwa token ada dan memiliki format yang sesuai
    //     if (!$token || !preg_match('/Bearer\s(\S+)/', $token, $matches)) {
    //         return $this->failUnauthorized('Invalid token');
    //     }

    //     // Ambil bagian token setelah "Bearer "
    //     $token = $matches[1];

    //     try {
    //         // Dekode token untuk mendapatkan informasi pengguna
    //         $key = getenv('JWT_SECRET');
            
    //         $decodedToken = JWT::decode($token, new Key($key, 'HS256'));
    //         $userId = $decodedToken->id; // Misalnya, ID pengguna ada di dalam token

    //         // Gunakan Model untuk mengambil data pengguna dari basis data
    //         $userModel = new UserModel();
    //         $userData = $userModel->find($userId);

    //         if (!$userData) {
    //             return $this->failNotFound('User not found');
    //         }

    //         // Kirim data pengguna sebagai respons
    //         return $this->respond(['status' => 200, 'user' => $userData]);
    //     } catch (\Exception $ex) {
    //         return $this->failUnauthorized('Unauthorized');
    //     }
    // }

    


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
