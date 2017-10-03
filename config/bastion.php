<?php

return [
    /**
     * Bastion specific tables.
     */
    'tables' => [
        'assigned_permissions' => 'assigned_permissions',
        'assigned_roles' => 'assigned_roles',
        'permissions' => 'permissions',
        'roles' => 'roles',
    ],

    /**
     * Application models.
     */
    'models' => [
        'assigned_permission' => Ethereal\Bastion\Database\AssignedPermission::class,
        'assigned_role' => Ethereal\Bastion\Database\AssignedRole::class,
        'permission' => Ethereal\Bastion\Database\Permission::class,
        'role' => Ethereal\Bastion\Database\Role::class,
    ],
];
