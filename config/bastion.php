<?php

return [
    /*
     * Bastion specific tabbles.
     */
    'tables' => [
        'assigned_roles' => 'assigned_roles',
        'abilities' => 'abilities',
        'permissions' => 'permissions',
    ],

    /*
     * Application models.
     */
    'models' => [
        'ability' => '',
        'role' => '',
    ],

    /*
     * A list of classes that have abilities and/or roles.
     * This list is used to clear cached items.
     */
    'authorities' => [
        App\User::class,
    ],
];