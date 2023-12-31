<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->match(['post', 'options'], 'user/signUp', 'UserController::signUp');
$routes->match(['post', 'options'], 'user/login', 'Login::index');
$routes->match(['get', 'options'], 'usersdata', "Login::getUserData", ['filter' => 'cors', 'authFilter']);
$routes->match(['post', 'options'], 'editprofil', "UserController::updateProfil", ['filter' => 'cors', 'authFilter']);

$routes->get('uploads/(:any)', 'ProfilController::index/$1');

$routes->match(['post', 'options'], 'folder/new', 'FolderController::create', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'folder', 'FolderController::index', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'folder/edit/(:segment)', 'FolderController::update/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['delete', 'options'], 'folder/delete/(:segment)', 'FolderController::delete/$1', ['filter' => 'cors', 'authFilter']);

$routes->match(['post', 'options'], 'catatan/new', 'CatatanController::create', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'catatan', 'CatatanController::index', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'catatan/folder/(:segment)', 'CatatanController::getByFolder/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'sampah', 'CatatanController::getTrash', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'favorite', 'CatatanController::getFavorite', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'catatan/edit/(:segment)', 'CatatanController::update/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['post', 'options'], 'catatan/kategori/(:segment)', 'CatatanController::kategori/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'catatan/unkategori/(:segment)', 'CatatanController::unkategori/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'catatan/sampah/(:segment)', 'CatatanController::trash/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'catatan/restore/(:segment)', 'CatatanController::restore/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'catatan/favorite/(:segment)', 'CatatanController::favorite/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['delete', 'options'], 'catatan/delete/(:segment)', 'CatatanController::delete/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'catatan/riwayat/(:segment)', 'CatatanController::riwayat/$1', ['filter' => 'cors', 'authFilter']);


$routes->match(['post', 'options'], 'todolist/new', 'TodoController::create', ['filter' => 'cors', 'authFilter']);
$routes->match(['post', 'options'], 'todolist/kategori/(:segment)', 'TodoController::kategori/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'todolist/unkategori/(:segment)', 'TodoController::unkategori/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'todolist/folder/(:segment)', 'TodoController::getByFolder/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'todolist', 'TodoController::index', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'todolist/sampah', 'TodoController::getTrash', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'todolist/favorite', 'TodoController::getFavorite', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'todolist/edit/(:segment)', 'TodoController::update/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'todolist/favorite/(:segment)', 'TodoController::favorite/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'todolist/sampah/(:segment)', 'TodoController::trash/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'todolist/restore/(:segment)', 'TodoController::restore/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['delete', 'options'], 'todolist/delete/(:segment)', 'TodoController::delete/$1', ['filter' => 'cors', 'authFilter']);

$routes->match(['post', 'options'], 'tugas/new/(:num)', 'TugasController::create/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['get', 'options'], 'tugas', 'TugasController::index', ['filter' => 'cors', 'authFilter']);
$routes->match(['put', 'options'], 'tugas/edit/(:segment)', 'TugasControllerr::update/$1', ['filter' => 'cors', 'authFilter']);
$routes->match(['delete', 'options'], 'tugas/delete/(:segment)', 'TugasControllerr::delete/$1', ['filter' => 'cors', 'authFilter']);






















$routes->match(['get', 'options'], 'user/getUserData', 'UserController::getUserData');
$routes->match(['put', 'options'], 'user/update/(:segment)', 'UserController::update/$1');
$routes->match(['delete', 'options'], 'user/delete/(:segment)', 'UserController::delete/$1');

$routes->group('user', function($routes) {
    // Rute untuk Sign Up (Membuat pengguna baru)
    $routes->post('signUp', 'UserController::signUp');

    // Mengambil data semua pengguna
    $routes->get('getUsers', 'UserController::getUser');

    // Mengambil data semua pengguna
    // $routes->get('getUsersData', 'UserController::getUserData');

    // Rute untuk Log In (Autentikasi pengguna)
    $routes->post('login', 'UserController::login');

    // Menampilkan formulir pengeditan data pengguna berdasarkan ID
    $routes->get('edit/(:num)', 'UserController::edit/$1');

    // Menampilkan data pengguna berdasarkan ID
    $routes->get('show/(:num)', 'UserController::show/$1');

    // Mengupdate data pengguna berdasarkan ID
    //$routes->put('update/(:num)', 'UserController::update/$1');

    // Menghapus data pengguna berdasarkan ID
    $routes->delete('delete/(:num)', 'UserController::delete/$1');

});


