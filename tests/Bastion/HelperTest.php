<?php

use Ethereal\Bastion\Bastion;
use Ethereal\Bastion\Database\Ability;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Relations\Relation;

class HelperTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_get_ability_model_class()
    {
        static::assertEquals(Ability::class, Helper::getAbilityModelClass());
    }

    /**
     * @test
     */
    public function it_can_get_ability_model()
    {
        static::assertInstanceOf(Ability::class, Helper::getAbilityModel());
    }

    /**
     * @test
     */
    public function it_can_get_ability_table()
    {
        static::assertEquals('abilities', Helper::getAbilityTable());
    }

    /**
     * @test
     */
    public function it_can_get_assigned_role_model_class()
    {
        static::assertEquals(AssignedRole::class, Helper::getAssignedRoleModelClass());
    }

    /**
     * @test
     */
    public function it_can_get_assigned_role_model()
    {
        static::assertInstanceOf(AssignedRole::class, Helper::getAssignedRoleModel());
    }

    /**
     * @test
     */
    public function it_can_get_assigned_role_table()
    {
        static::assertEquals('assigned_roles', Helper::getAssignedRoleTable());
    }

    /**
     * @test
     */
    public function it_can_get_role_model_class()
    {
        static::assertEquals(Role::class, Helper::getRoleModelClass());
    }

    /**
     * @test
     */
    public function it_can_get_role_model()
    {
        static::assertInstanceOf(Role::class, Helper::getRoleModel());
    }

    /**
     * @test
     */
    public function it_can_get_role_table()
    {
        static::assertEquals('roles', Helper::getRoleTable());
    }

    /**
     * @test
     */
    public function it_can_get_permissions_model_class()
    {
        static::assertEquals(Permission::class, Helper::getPermissionModelClass());
    }

    /**
     * @test
     */
    public function it_can_get_permissions_model()
    {
        static::assertInstanceOf(Permission::class, Helper::getPermissionModel());
    }

    /**
     * @test
     */
    public function it_can_get_permissions_table()
    {
        static::assertEquals('permissions', Helper::getPermissionTable());
    }

    /**
     * @test
     */
    public function it_can_get_bastion_instance()
    {
        $this->app->instance(Bastion::class, new Bastion($this->app, null));

        self::assertInstanceOf(Bastion::class, Helper::bastion());
    }

    /**
     * @test
     */
    public function it_can_get_morph_name_from_class()
    {
        Relation::morphMap([
            'ability' => Ability::class,
        ]);

        self::assertEquals('ability', Helper::getMorphOfClass(Ability::class));
        self::assertEquals(Role::class, Helper::getMorphOfClass(Role::class));
    }
}
