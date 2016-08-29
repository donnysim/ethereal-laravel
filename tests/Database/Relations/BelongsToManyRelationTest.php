<?php

use Ethereal\Database\Ethereal;
use Ethereal\Database\Relations\Handlers\BelongsToManyHandler;
use Illuminate\Database\Eloquent\Collection;

class BelongsToManyRelationTest extends BaseTestCase
{
    public function test_model_array_is_kept_intact()
    {
        $user = new TestUserModel;
        $user->setRelation('rawRoles', [
            new TestRoleModel,
            new TestRoleModel,
            new TestRoleModel,
        ]);

        static::assertTrue($user->relationLoaded('rawRoles'));
        static::assertInstanceOf(Collection::class, $user->rawRoles);
        static::assertFalse($user->rawRoles->isEmpty());

        foreach ($user->rawRoles as $role) {
            static::assertInstanceOf(TestRoleModel::class, $role);
        }
    }

    public function test_associative_array_is_converted_into_models()
    {
        $user = new TestUserModel;
        $user->setRelation('rawRoles', [
            ['title' => '1'],
            ['title' => '2'],
            ['title' => '3'],
        ]);

        static::assertTrue($user->relationLoaded('rawRoles'));
        static::assertInstanceOf(Collection::class, $user->rawRoles);
        static::assertFalse($user->rawRoles->isEmpty());

        foreach ($user->rawRoles as $role) {
            static::assertInstanceOf(TestRoleModel::class, $role);
        }
    }

    public function test_empty_array_is_allowed_and_converted_into_collection()
    {
        $user = new TestUserModel;
        $user->setRelation('rawRoles', []);

        static::assertTrue($user->relationLoaded('rawRoles'));
        static::assertInstanceOf(Collection::class, $user->rawRoles);
    }

    public function test_array_type_is_determined_correctly()
    {
        static::assertEquals(BelongsToManyHandler::NORMAL, BelongsToManyHandler::getArrayType([
            new TestRoleModel,
            new TestRoleModel,
            new TestRoleModel,
        ]));

        static::assertEquals(BelongsToManyHandler::NORMAL, BelongsToManyHandler::getArrayType([
            ['title' => '1'],
            ['title' => '2'],
            ['title' => '3'],
        ]));

        static::assertEquals(BelongsToManyHandler::SYNC, BelongsToManyHandler::getArrayType([
            1, 20, 35
        ]));

        static::assertEquals(BelongsToManyHandler::SYNC, BelongsToManyHandler::getArrayType([
            [],
            1,
            3
        ]));

        static::assertEquals(BelongsToManyHandler::SYNC, BelongsToManyHandler::getArrayType([
            1 => [],
            20 => [],
            35 => []
        ]));

        static::assertEquals(BelongsToManyHandler::SYNC, BelongsToManyHandler::getArrayType([
            1 => [],
            20,
            35 => []
        ]));

        static::assertEquals(BelongsToManyHandler::SYNC, BelongsToManyHandler::getArrayType([
            1 => [],
            [],
            35 => []
        ]));
    }

    public function test_empty_array_as_model_is_not_allowed()
    {
        $user = new TestUserModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('rawRoles', [
            ['title' => '1'],
            [],
            ['title' => '3'],
        ]);
    }

    public function test_index_of_zero_is_not_allowed_in_sync()
    {
        $user = new TestUserModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('rawRoles', [
            ['title' => '1'],
            1,
            3,
        ]);
    }

    public function test_model_is_not_allowed_in_sync_array()
    {
        $user = new TestUserModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('rawRoles', [
            1 => ['title' => '1'],
            new TestProfileModel,
            ['title' => '3'],
        ]);
    }

    public function test_mixed_array_of_assoc_and_model_is_allowed()
    {
        $user = new TestUserModel;
        $user->setRelation('rawRoles', [
            ['title' => '1'],
            new TestRoleModel,
            ['title' => '3'],
        ]);

        static::assertTrue($user->relationLoaded('rawRoles'));
        static::assertInstanceOf(Collection::class, $user->rawRoles);
        static::assertFalse($user->rawRoles->isEmpty());

        foreach ($user->rawRoles as $role) {
            static::assertInstanceOf(TestRoleModel::class, $role);
        }
    }

    public function test_models_are_saved_and_attached()
    {
        $user = TestUserModel::random();
        $user->setRelation('rawRoles', [
            ['name' => 's&a-1'],
            ['name' => 's&a-2'],
            ['name' => 's&a-3'],
        ]);

        $user->smartPush();

        foreach ($user->rawRoles as $role) {
            static::assertTrue($role->exists);
        }

        $count = $this->app['db']->table('role_user')->where('user_id', $user->getKey())->count();
        static::assertEquals(3, $count);
    }

    public function test_models_are_detached()
    {
        $user = TestUserModel::random();
        $user->setRelation('rawRoles', [
            ['name' => 's&a-1'],
            ['name' => 's&a-2'],
            ['name' => 's&a-3'],
        ]);

        $user->smartPush();

        $user->setRelation('rawRoles', [
            $user->rawRoles->first()
        ]);

        static::assertEquals(1, $user->rawRoles->count());

        $user->smartPush([
            'relations' => [
                'rawRoles' => Ethereal::OPTION_DETACH
            ]
        ]);

        $count = $this->app['db']->table('role_user')->where('user_id', $user->getKey())->count();
        static::assertEquals(2, $count);
    }

    public function test_model_is_detached_on_delete()
    {
        $user = TestUserModel::random();
        $user->setRelation('rawRoles', [
            ['name' => 's&a-1'],
            ['name' => 's&a-2'],
            ['name' => 's&a-3'],
        ]);

        $user->smartPush();

        $user->setRelation('rawRoles', [
            $user->rawRoles->first()
        ]);

        static::assertEquals(1, $user->rawRoles->count());

        $user->smartPush([
            'relations' => [
                'rawRoles' => Ethereal::OPTION_DELETE
            ]
        ]);

        static::assertEquals(0, $user->rawRoles->count());
        $count = $this->app['db']->table('role_user')->where('user_id', $user->getKey())->count();
        static::assertEquals(2, $count);
    }

    public function test_models_are_synced()
    {
        $user = TestUserModel::random();
        $user->setRelation('rawRoles', [
            ['name' => 's&a-1'],
            ['name' => 's&a-2'],
            ['name' => 's&a-3'],
        ]);

        $user->smartPush();

        $user->setRelation('rawRoles', [
            $user->rawRoles->first()
        ]);

        static::assertEquals(1, $user->rawRoles->count());

        $user->smartPush([
            'relations' => [
                'rawRoles' => Ethereal::OPTION_SYNC
            ]
        ]);

        static::assertEquals(1, $user->rawRoles->count());
        $count = $this->app['db']->table('role_user')->where('user_id', $user->getKey())->count();
        static::assertEquals(1, $count);
    }
}