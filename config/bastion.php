<?php

return [
    /*
     * Bastion specific tables.
     */
    'tables' => [
        'abilities' => 'abilities',
        'assigned_roles' => 'assigned_roles',
        'permissions' => 'permissions',
        'roles' => 'roles',
    ],

    /*
     * Application models.
     */
    'models' => [
        'ability' => \Ethereal\Bastion\Database\Ability::class,
        'assigned_role' => \Ethereal\Bastion\Database\AssignedRole::class,
        'permission' => \Ethereal\Bastion\Database\Permission::class,
        'role' => \Ethereal\Bastion\Database\Role::class,
    ],
];
