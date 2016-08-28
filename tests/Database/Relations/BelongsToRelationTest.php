<?php

use Ethereal\Database\Ethereal;
use Illuminate\Database\QueryException;

class BelongsToRelationTest extends BaseTestCase
{
    public function test_associative_array_should_be_wrapped_into_model()
    {
        $profile = new TestProfileModel;
        $profile->setRelation('user', [
            'email' => 'email@email.com',
            'password' => 'password',
        ]);

        self::assertTrue($profile->relationLoaded('user'));
        self::assertInstanceOf(TestUserModel::class, $profile->user);
    }

    public function test_empty_arrays_are_not_allowed_for_singular_relations()
    {
        $profile = new TestProfileModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $profile->setRelation('user', []);
    }

    public function test_skips_non_existing_model_on_delete()
    {
        $profile = TestProfileModel::random();
        $profile->setRelation('user', TestUserModel::random());

        $this->setExpectedException(QueryException::class, "NOT NULL constraint failed: profiles.user_id (SQL: insert into \"profiles\" (\"name\", \"last_name\", \"user_id\", \"updated_at\", \"created_at\") values ({$profile->name}, {$profile->last_name}");
        $profile->smartPush([
            'relations' => [
                'user' => Ethereal::OPTION_DELETE,
            ]
        ]);
    }

    public function test_unsets_user_id_after_user_relation_delete()
    {
        $profile = TestProfileModel::random();
        $profile->setRelation('user', TestUserModel::random());
        $profile->smartPush();

        $this->setExpectedException(QueryException::class, 'NOT NULL constraint failed: profiles.user_id (SQL: update "profiles" set "user_id" = ,');
        $profile->smartPush([
            'relations' => [
                'user' => Ethereal::OPTION_DELETE,
            ]
        ]);
    }

    public function test_relation_model_must_match_expected()
    {
        $user = new TestProfileModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('user', new TestCommentModel);
    }

    public function test_model_has_key_linking_to_relation_after_save_and_retrieved_properly()
    {
        $profile = TestProfileModel::smartCreate([
            'name' => 'Name',
            'last_name' => 'Last Name',
            'user' => [
                'email' => 'email@email.com',
                'password' => 'password',
            ]
        ]);

        static::assertTrue($profile->exists);
        static::assertTrue($profile->user->exists);
        static::assertEquals($profile->user_id, $profile->user->getKey());

        unset($profile['user']);
        self::assertInstanceOf(TestUserModel::class, $profile->user);
    }
}