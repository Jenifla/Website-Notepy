<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DaftarTugas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_tugas' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'auto_increment' => true,
            ],
            'id_todolist' => [
                'type' => 'BIGINT',
                'constraint' => 20,
            ],
            'tugas' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'status' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
        ]);
        $this->forge->addKey('id_tugas', true);
        $this->forge->addForeignKey('id_todolist', 'todolist', 'id_todolist');
        $this->forge->createTable('tugas');
    }

    public function down()
    {
        $this->forge->dropTable('tugas');
    }
}
