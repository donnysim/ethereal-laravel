<?php

use Ethereal\Database\Relations\Handlers\HasOneHandler;
use Ethereal\Database\Relations\Manager;

class HasOneHandlerTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_builds_a_model()
    {
        $user = new TestUserModel;
        $handler = new HasOneHandler($user->profile(), 'profile', new TestProfileModel, Manager::SAVE);

        $model = $handler->build();

        self::assertInstanceOf(TestProfileModel::class, $model);
    }

    /**
     * @test
     */
    public function it_saves_and_links_the_models()
    {
        $this->migrate();

        $user = new TestUserModel(['email' => 'john@example.com']);
        $profile = new TestProfileModel(['name' => 'John']);
        $user->setRelation('profile', $profile);

        $manager = new Manager($user);
        $manager->save();

        self::assertTrue($user->exists);
        self::assertTrue($profile->exists);
        self::assertEquals($user->getKey(), $profile->user_id);
    }

    /**
     * @test
     */
    public function it_deletes_and_unlinks_the_models()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $profile = TestProfileModel::create(['user_id' => $user->getKey(), 'name' => 'John']);
        $user->setRelation('profile', $profile);

        $manager = new Manager($user, [
            'relations' => [
                'profile' => Manager::DELETE,
            ]
        ]);
        $manager->save();

        self::assertTrue($user->exists);
        self::assertFalse($profile->exists);
        self::assertNull($profile->user_id);
    }

    /**
     * @test
     */
    public function it_waits_for_parent_to_be_saved()
    {
        $user = new TestUserModel;
        $handler = new HasOneHandler($user->profile(), 'profile', new TestProfileModel, Manager::SAVE);

        self::assertTrue($handler->isWaitingForParent());

        $user->exists = true;

        self::assertFalse($handler->isWaitingForParent());
    }
}
