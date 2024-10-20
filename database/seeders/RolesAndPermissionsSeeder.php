<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $projectManagerRole = Role::create(['name' => 'project_manager']);
        $userRole = Role::create(['name' => 'user']);

        // Create permissions
        $permissions = [
            'create tasks',
            'view tasks',
            'edit tasks',
            'delete tasks',
            'update status',
            'assign user',
            'reassign user',
            'filter tasks',
            'filter blocked dependices',
            'manage users',
            'generate reports',
            'create comment',
            'update comment',
            'view comment',
            'delete comment',
            'create attachments',
            'update attachments',
            'view attachments',
            'delete attachments',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());
        $projectManagerRole->givePermissionTo(['create tasks',
            'view tasks',
            'edit tasks',
            'delete tasks',
            'update status',
            'assign user',
            'reassign user',
            'filter tasks',
            'filter blocked dependices',
            'manage users',
            'generate reports',
            'view comment',
            'view attachments',
        ]);
        $userRole->givePermissionTo(['view tasks',
            'edit tasks',
            'create comment',
            'update comment',
            'view comment',
            'delete comment',
            'create attachments',
            'update attachments',
            'view attachments',
            'delete attachments',
            ]);

        // Create example users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'), // Use hashed passwords
        ]);
        $admin->assignRole($adminRole);

        $projectManager = User::create([
            'name' => 'Project Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);
        $projectManager->assignRole($projectManagerRole);

        $user = User::create([
            'name' => 'Normal User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($userRole);
    }
}
