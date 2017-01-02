<?php

use Ethereal\Database\Relations\Handlers\HasManyHandler;
use Ethereal\Database\Relations\Manager;
use Illuminate\Database\Eloquent\Collection;

class HasManyHandlerTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_saves_and_links_the_models()
    {
        $this->migrate();

        $user = new TestUserModel(['email' => 'john@example.com']);
        $profile1 = new TestProfileModel(['name' => 'John']);
        $profile2 = new TestProfileModel(['name' => 'Jane']);
        $user->setRelation('profiles', new Collection([$profile1, $profile2]));

        $manager = new Manager($user);
        $manager->save();

        self::assertTrue($user->exists);
        self::assertTrue($profile1->exists);
        self::assertTrue($profile2->exists);
        self::assertEquals($user->getKey(), $profile1->user_id);
        self::assertEquals($user->getKey(), $profile2->user_id);
    }

    /**
     * @test
     */
    public function it_deletes_and_unlinks_the_models()
    {
        $this->migrate();

        $user = new TestUserModel(['email' => 'john@example.com']);
        $profile1 = new TestProfileModel(['name' => 'John']);
        $profile2 = new TestProfileModel(['name' => 'Jane']);
        $user->setRelation('profiles', new Collection([$profile1, $profile2]));

        $manager = new Manager($user, [
            'relations' => [
                'profiles' => Manager::DELETE,
            ]
        ]);
        $manager->save();

        self::assertTrue($user->exists);
        self::assertFalse($profile1->exists);
        self::assertFalse($profile2->exists);
        self::assertNull($profile1->user_id);
        self::assertNull($profile2->user_id);
    }

    /**
     * @test
     */
    public function it_waits_for_parent_to_be_saved()
    {
        $user = new TestUserModel;
        $handler = new HasManyHandler($user->profile(), 'profiles', new Collection([new TestProfileModel]), Manager::SAVE);

        self::assertTrue($handler->isWaitingForParent());

        $user->exists = true;

        self::assertFalse($handler->isWaitingForParent());
    }
}
