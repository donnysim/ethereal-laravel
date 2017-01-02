<?php

use Ethereal\Database\Relations\Handlers\BelongsToManyHandler;
use Ethereal\Database\Relations\Manager;
use Illuminate\Database\Eloquent\Collection;

class BelongsToManyHandlerTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_determine_array_type()
    {
        static::assertEquals(BelongsToManyHandler::NORMAL, BelongsToManyHandler::getArrayType([
            new TestUserModel,
            new TestUserModel,
            new TestUserModel,
        ]));

        static::assertEquals(BelongsToManyHandler::NORMAL, BelongsToManyHandler::getArrayType([
            ['title' => '1'],
            ['title' => '2'],
            ['title' => '3'],
        ]));

        static::assertEquals(BelongsToManyHandler::SYNC, BelongsToManyHandler::getArrayType([
            1,
            20,
            35
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

    /**
     * @test
     */
    public function it_saves_and_links_the_models()
    {
        $this->migrate();

        $profile = new TestProfileModel(['name' => 'John or Jane']);
        $user1 = new TestUserModel(['email' => 'john@example.com']);
        $user2 = new TestUserModel(['email' => 'jane@example.com']);
        $profile->setRelation('users', new Collection([$user1, $user2]));

        $manager = new Manager($profile);
        $manager->save();

        self::assertTrue($profile->exists);
        self::assertTrue($user1->exists);
        self::assertTrue($user2->exists);

        static::assertEquals(2,
            $this->app['db']->table('profile_user')
                ->where('profile_id', $profile->getKey())
                ->whereIn('user_id', [$user1->getKey(), $user2->getKey()])
                ->count()
        );
    }

    /**
     * @test
     */
    public function it_deletes_and_unlinks_the_models()
    {
        $this->migrate();

        $profile = new TestProfileModel(['name' => 'John or Jane']);
        $user1 = new TestUserModel(['email' => 'john@example.com']);
        $user2 = new TestUserModel(['email' => 'jane@example.com']);
        $profile->setRelation('users', new Collection([$user1, $user2]));

        $manager = new Manager($profile, [
            'relations' => [
                'users' => Manager::DELETE,
            ]
        ]);
        $manager->save();

        self::assertTrue($profile->exists);
        self::assertFalse($user1->exists);
        self::assertFalse($user2->exists);

        static::assertEquals(0,
            $this->app['db']->table('profile_user')
                ->where('profile_id', $profile->getKey())
                ->whereIn('user_id', [$user1->getKey(), $user2->getKey()])
                ->count()
        );
    }

    /**
     * @test
     */
    public function it_waits_for_parent_to_be_saved()
    {
        $profile = new TestProfileModel(['name' => 'John or Jane']);
        $handler = new BelongsToManyHandler($profile->users(), 'profiles', new Collection([new TestUserModel]), Manager::SAVE);

        self::assertTrue($handler->isWaitingForParent());

        $profile->exists = true;

        self::assertFalse($handler->isWaitingForParent());
    }
}
