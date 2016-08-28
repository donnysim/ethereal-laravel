<?php

use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class HasManyRelationTest extends BaseTestCase
{
    public function test_array_should_be_wrapped_into_model()
    {
        $user = new TestUserModel;
        $user->setRelation('comments', [
            [
                'text' => 'Lorem 1',
            ],
            [
                'text' => 'Lorem 2',
            ]
        ]);

        self::assertTrue($user->relationLoaded('comments'));

        foreach ($user->comments as $comment) {
            self::assertInstanceOf(TestCommentModel::class, $comment);
        }
    }

    public function test_is_attached_to_parent_after_save_and_retrieved_properly()
    {
        $user = TestUserModel::random();
        $user->setRelation('comments', [
            [
                'text' => 'Lorem 1',
            ],
            [
                'text' => 'Lorem 2',
            ]
        ]);
        $user->smartPush();

        static::assertTrue($user->exists);

        foreach ($user->comments as $comment) {
            static::assertTrue($comment->exists);
            static::assertEquals($user->getKey(), $comment->user_id);
        }

        unset($user['comments']);
        self::assertCount(2, $user->comments);
    }

    public function test_collection_relation_expects_proper_array()
    {
        $user = new TestUserModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('comments', [1, 2, 3]);
    }

    public function test_collection_relation_expects_proper_collection()
    {
        $user = new TestUserModel;

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('comments', new Collection([1, 2, 3]));
    }

    public function test_collection_relation_expects_proper_models()
    {
        $user = new TestUserModel;

        $user->setRelation('comments', new Collection([new TestCommentModel]));
        $user->setRelation('comments', [new TestCommentModel]);

        $this->setExpectedException(InvalidArgumentException::class);
        $user->setRelation('comments', new Collection([new TestCommentModel, new TestProfileModel]));
    }

    public function test_collection_forgets_deleted_model()
    {
        $user = TestUserModel::random();
        $user->setRelation('comments', [
            [
                'text' => 'Lorem 1',
            ],
            [
                'text' => 'Lorem 2',
            ]
        ]);
        $user->smartPush();

        $user->smartPush([
            'relations' => [
                'comments' => Ethereal::OPTION_DELETE,
            ]
        ]);

        self::assertCount(0, $user->comments);
    }

    public function test_relation_can_be_synced()
    {
        $user = TestUserModel::random();
        $user->setRelation('comments', [
            [
                'text' => 'Lorem 1',
            ],
            [
                'text' => 'Lorem 2',
            ],
            [
                'text' => 'Lorem 3',
            ],
            [
                'text' => 'Lorem 4',
            ]
        ]);

        $user->smartPush();

        $user->comments->forget([2, 3]);
        $user->smartPush(['relations' => [
            'comments' => Ethereal::OPTION_SYNC
        ]]);

        unset($user['comments']);
        self::assertEquals(2, $user->comments->count());
    }

    public function test_does_not_call_event_with_no_dispatcher()
    {
        $user = TestUserModel::random();
        $user->setRelation('comments', [
            [
                'text' => 'Lorem 1',
            ],
        ]);

        $user->smartPush();

        TestUserModel::syncing(function () {
            throw new Exception('Should not be called.');
        });

        $dispatcher = Model::getEventDispatcher();
        Model::unsetEventDispatcher();

        $user->smartPush(['relations' => [
            'comments' => Ethereal::OPTION_SYNC
        ]]);

        Model::setEventDispatcher($dispatcher);
        static::setExpectedException(Exception::class, 'Should not be called.');

        $user->smartPush(['relations' => [
            'comments' => Ethereal::OPTION_SYNC
        ]]);
    }
}