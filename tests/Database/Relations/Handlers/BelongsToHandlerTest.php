<?php

use Ethereal\Database\Relations\Handlers\BelongsToHandler;
use Ethereal\Database\Relations\Manager;

class BelongsToHandlerTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_saves_and_links_the_models()
    {
        $this->migrate();

        $profile = new TestProfileModel(['name' => 'John']);
        $user = new TestUserModel(['email' => 'john@example.com']);
        $profile->setRelation('user', $user);

        $manager = new Manager($profile);
        $manager->save();

        self::assertTrue($profile->exists);
        self::assertTrue($user->exists);
        self::assertNotNull($profile->user_id);
        self::assertEquals($profile->user_id, $user->getKey());
    }

    /**
     * @test
     */
    public function it_deletes_and_unlinks_the_models()
    {
        $this->migrate();

        $profile = new TestProfileModel(['name' => 'John']);
        $user = new TestUserModel(['email' => 'john@example.com']);
        $profile->setRelation('user', $user);

        $manager = new Manager($profile, [
            'relations' => [
                'user' => Manager::DELETE,
            ]
        ]);
        $manager->save();

        self::assertTrue($profile->exists);
        self::assertFalse($user->exists);
        self::assertNull($profile->user_id);
    }

    /**
     * @test
     */
    public function it_does_not_wait_for_parent_to_be_saved()
    {
        $profile = new TestProfileModel;
        $handler = new BelongsToHandler($profile->user(), 'user', new TestUserModel, Manager::SAVE);

        self::assertFalse($handler->isWaitingForParent());

        $profile->exists = true;

        self::assertFalse($handler->isWaitingForParent());
    }
}
