<?php

use Ethereal\Database\Ethereal;

class HasOneRelationTest extends BaseTestCase
{
    public function test_associative_array_should_be_wrapped_into_model()
    {
        $user = new TestUserModel;
        $user->setRelation('profile', [
            'name' => 'Name',
            'last_name' => 'Last Name',
        ]);

        self::assertTrue($user->relationLoaded('profile'));
        self::assertInstanceOf(TestProfileModel::class, $user->profile);
    }

    public function test_skips_non_existing_model_on_delete()
    {
        $user = TestUserModel::random(true);
        $user->smartPush([
            'relations' => [
                'profile' => Ethereal::OPTION_DELETE,
            ]
        ]);

        self::assertNull($user->profile);
    }

    public function test_empty_arrays_are_not_allowed()
    {
        $user = new TestUserModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('profile', []);
    }

    public function test_relation_model_must_match_expected()
    {
        $user = new TestUserModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('profile', new TestUserModel);
    }

    public function test_is_attached_to_parent_after_save_and_retrieved_properly()
    {
        $user = TestUserModel::random(true);
        $user->smartPush();

        static::assertTrue($user->exists);
        static::assertTrue($user->profile->exists);
        static::assertEquals($user->getKey(), $user->profile->user_id);

        unset($user['profile']);
        self::assertInstanceOf(TestProfileModel::class, $user->profile);
    }

    public function test_singular_relation_is_set_to_null_on_delete()
    {
        $user = TestUserModel::random(true);
        $user->smartPush();

        $user->smartPush(['relations' => [
            'profile' => Ethereal::OPTION_DELETE,
        ]]);

        self::assertNull($user->profile);
    }
}