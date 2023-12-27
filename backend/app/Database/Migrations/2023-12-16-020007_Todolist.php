<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Todolist extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_todolist' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'auto_increment' => true,
            ],
            'id_user' => [
                'type' => 'BIGINT',
                'constraint' => 20,
            ],
            'id_folder' => [ 
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => true,
            ],
            'judul_todolist' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'difavoritkan' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'dihapus' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'tgl_buat' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'tgl_edit' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'timestamp' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            // Kolom lainnya sesuai kebutuhan
        ]);
        $this->forge->addKey('id_todolist', true);
        $this->forge->addForeignKey('id_user', 'user', 'id_user');
        $this->forge->addForeignKey('id_folder', 'folder', 'id_folder');
        $this->forge->createTable('todolist');
    }

    public function down()
    {
        $this->forge->dropTable('todolist');
    }
}
