<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'edit transactions'      => 'Может редактировать записи',
            'edit date' => 'Может устанавливать прошедшие даты',
            'view transfers'          => 'Может управлять трансферами',
            'view analytics'         => 'Может смотреть аналитику',
            'delete transactions'    => 'Может удалять записи',
            'view projects'        => 'Может управлять проектами',
            'view objects'  => 'Может управлять контрагентами',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name]);
        }
    }
}
