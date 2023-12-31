<?php

namespace App\Controllers;

class ProfilController extends BaseController
{
    public function index($file)
    {
        $path = FCPATH . 'path/' . $file;

        if (file_exists($path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);

            header('Content-Type: ' . $mime);
            readfile($path);
        } else {
            // Handle file not found (e.g., show a default image or return an error)
            echo 'File not found';
        }
    }
}