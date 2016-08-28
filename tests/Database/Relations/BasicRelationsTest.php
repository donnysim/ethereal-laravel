<?php

use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BasicRelationsTest extends BaseTestCase
{
    public function test_works_as_intended_when_no_relations_are_present()
    {
        $user = TestUserModel::random();
        self::assertTrue($user->smartPush());
    }

    public function test_non_existing_relations_are_allowed()
    {
        $user = new TestUserModel;
        $user->setRelation('dummy', []);

        self::assertTrue($user->relationLoaded('dummy'));
        self::assertEquals([], $user->dummy);
    }

    public function test_allows_null_relation_values()
    {
        $user = new TestUserModel;

        $user->setRawRelation('profile', null);
        self::assertTrue($user->relationLoaded('profile'));
        self::assertNull($user->profile);
    }

    public function test_can_set_raw_relation()
    {
        $user = new TestUserModel;

        $user->setRawRelation('test', []);
        self::assertTrue($user->relationLoaded('test'));
        self::assertEquals([], $user->test);

        $user->setRawRelation('test2', null);
        self::assertTrue($user->relationLoaded('test2'));
        self::assertNull($user->test2);
    }

    public function test_skips_invalid_relations()
    {
        // Should not crash if everything is correct
        $user = TestUserModel::random();
        $user->setRawRelation('skipOption', []);
        $user->setRawRelation('null', null);
        $user->setRawRelation('array', []);
        $user->setRawRelation('emptyCollection', Collection::make([]));
        $user->setRawRelation('noMethod', Collection::make(['1', '2']));

        self::assertTrue($user->smartPush(['relations' => [
            'skipOption' => Ethereal::OPTION_SKIP,
        ]]));
    }

    public function test_relation_model_is_checked_for_existence_when_key_is_present()
    {
        $user = new TestUserModel;
        $user->setRelation('profile', [
            'user_id' => 1,
            'name' => 'Name',
            'last_name' => 'Last Name',
        ]);

        self::assertFalse($user->profile->exists);
        $user->profile->save();

        self::assertTrue($user->profile->exists);
        $profileId = $user->profile->getKey();
        unset($user['profile']);

        $user->setRelation('profile', [
            'id' => $profileId,
            'name' => 'New Name',
            'last_name' => 'New Last Name',
        ]);

        self::assertTrue($user->profile->exists);
    }

    public function test_smart_new_creates_new_instance_without_saving()
    {
        $user = TestUserModel::smartNew([
            'email' => 'email@email.com',
            'password' => 'my_password',
        ]);

        self::assertFalse($user->exists);
    }

    public function test_smart_create_creates_new_instance_and_saves()
    {
        $user = TestUserModel::smartCreate([
            'email' => 'email@email.com',
            'password' => 'my_password',
        ]);

        self::assertTrue($user->exists);
    }

    public function test_fill_ignores_relations()
    {
        $user = new TestUserModel;
        $user->fill([
            'email' => 'email@email.com',
            'password' => 'my_password',
            'profile' => [
                'name' => 'Name',
                'last_name' => 'Last Name',
            ],
        ]);

        self::assertEquals([
            'email' => 'email@email.com',
            'password' => 'my_password',
        ], $user->toArray());
    }

    public function test_unguarded_model_fill_ignores_relations()
    {
        TestUserModel::unguard();
        $user = new TestUserModel;
        $user->fill([
            'email' => 'email@email.com',
            'password' => 'my_password',
            'profile' => [
                'name' => 'Name',
                'last_name' => 'Last Name',
            ],
        ]);
        TestUserModel::reguard();

        self::assertEquals([
            'email' => 'email@email.com',
            'password' => 'my_password',
        ], $user->toArray());
    }

    public function test_smart_fill_includes_relations()
    {
        $attributes = [
            'email' => 'email@email.com',
            'password' => 'my_password',
            'profile' => [
                'name' => 'Name',
                'last_name' => 'Last Name',
            ],
        ];

        $user = TestUserModel::smartNew($attributes);
        self::assertEquals($attributes, $user->toArray());
    }

    public function test_remove_on_delete_options_is_respected()
    {
        $user = TestUserModel::random(true);
        $user->smartPush();
        $user->smartPush([
            'removeRelationModelOnDelete' => false,
            'relations' => [
                'profile' => Ethereal::OPTION_DELETE,
            ],
        ]);

        self::assertNotNull($user->profile);
    }

    public function test_nested_relation_options_are_read_correctly()
    {
        $user = TestUserModel::random(true);
        $user->profile->setRelation('user', [
            'email' => 'test',
            'password' => 'another one',
        ]);
        $user->smartPush();
        $user->smartPush(['relations' => [
            'profile' => Ethereal::OPTION_SAVE,
            'profile.user' => Ethereal::OPTION_DELETE,
        ]]);

        self::assertNull($user->profile->user);
    }

    public function test_can_get_handler_for_loaded_relation()
    {
        $user = TestUserModel::random(true);
        $handler = $user->profileHandler();

        self::assertInstanceOf(\Ethereal\Database\Relations\Handlers\HasOneHandler::class, $handler);
    }

    public function test_handler_returns_null_on_missing_relation()
    {
        $user = TestUserModel::random();
        $handler = $user->profileHandler();

        self::assertNull($handler);
    }

    public function test_can_autoload_relation_on_handler_call()
    {
        $user = TestUserModel::random(true);
        $user->smartPush();
        unset($user['profile']);

        self::assertFalse($user->relationLoaded('profile'));

        $handler = $user->profileHandler(true);

        self::assertTrue($user->relationLoaded('profile'));
        self::assertInstanceOf(\Ethereal\Database\Relations\Handlers\HasOneHandler::class, $handler);
    }

    public function test_relation_handler_call_accepts_callback()
    {
        $user = TestUserModel::random(true);
        $user->smartPush();
        unset($user['profile']);

        self::assertFalse($user->relationLoaded('profile'));

        $handler = $user->profileHandler(true, function ($query) {
            $query->select(['id', 'user_id', 'name']);
        });

        self::assertTrue($user->relationLoaded('profile'));
        self::assertInstanceOf(\Ethereal\Database\Relations\Handlers\HasOneHandler::class, $handler);
        self::assertEquals(['id', 'user_id', 'name'], array_keys($user->getRelation('profile')->getAttributes()));
    }
}