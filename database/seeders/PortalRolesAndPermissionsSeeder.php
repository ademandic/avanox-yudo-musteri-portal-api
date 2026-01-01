<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PortalRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Portal guard için izinler oluştur
        $guard = 'api';

        // Permissions
        $permissions = [
            // Talep izinleri
            'view requests',
            'create requests',
            'update requests',
            'cancel requests',

            // Dosya izinleri
            'upload files',
            'download files',
            'delete files',

            // Kullanıcı yönetimi izinleri (Admin)
            'view users',
            'invite users',
            'manage users',

            // Davetiye izinleri
            'send invitations',
            'cancel invitations',

            // Ayar izinleri
            'update profile',
            'change password',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        // Roller oluştur
        // Portal User - Normal kullanıcı
        $portalUser = Role::firstOrCreate([
            'name' => 'Portal User',
            'guard_name' => $guard,
        ]);

        $portalUser->syncPermissions([
            'view requests',
            'create requests',
            'update requests',
            'cancel requests',
            'upload files',
            'download files',
            'delete files',
            'update profile',
            'change password',
        ]);

        // Portal Admin - Firma yöneticisi
        $portalAdmin = Role::firstOrCreate([
            'name' => 'Portal Admin',
            'guard_name' => $guard,
        ]);

        $portalAdmin->syncPermissions([
            'view requests',
            'create requests',
            'update requests',
            'cancel requests',
            'upload files',
            'download files',
            'delete files',
            'view users',
            'invite users',
            'manage users',
            'send invitations',
            'cancel invitations',
            'update profile',
            'change password',
            'manage settings',
        ]);

        $this->command->info('Portal roles and permissions created successfully.');
    }
}
