<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Folder extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_folder' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'auto_increment' => true,
            ],
            'id_user' => [
                'type' => 'BIGINT',
                'constraint' => 20,
            ],
            'nama_folder' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'tgl_buat' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'tgl_edit' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_folder', true);
        $this->forge->addForeignKey('id_user', 'user', 'id_user');
        $this->forge->createTable('folder');
    }

    public function down()
    {
        $this->forge->dropTable('folder');
    }
}
