<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class User extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_user' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'auto_increment' => true
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'password' => [
                'type' => 'CHAR',
                'constraint' => 60,
            ],
            'foto_profil' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'lokasi' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'no_tlpn' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'Tgl_registrasi' => [
                'type' => 'TIMESTAMP',
            ],
            'Tgl_edit' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_user', true);
        $this->forge->createTable('user');
    }

    public function down()
    {
        $this->forge->dropTable('user');
    }
}
