<?php

use Ethereal\Database\Ethereal;
use Ethereal\Database\Relations\Manager;

class ManagerTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_check_if_object_is_soft_deleting()
    {
        self::assertFalse(Manager::isSoftDeleting(new Ethereal));
        self::assertTrue(Manager::isSoftDeleting(new SoftDeletingClass));
    }

    /**
     * @test
     */
    public function it_provides_default_options()
    {
        self::assertEquals(Manager::SAVE | Manager::ATTACH, Manager::getDefaultOptions());
    }

    /**
     * @test
     */
    public function it_can_register_handler_and_check_if_relation_can_be_handled()
    {
        self::assertFalse(Manager::canHandle('RandomClass'));

        Manager::register('RandomClass', 'SomeClass');

        self::assertTrue(Manager::canHandle('RandomClass'));
    }

    /**
     * @test
     */
    public function it_can_determine_if_the_relation_can_should_be_skipped()
    {
        // Relations marked as skip should be skipped
        self::assertTrue(Manager::shouldSkipRelation(null, Manager::SKIP));

        // Invalid types should be skipped
        self::assertTrue(Manager::shouldSkipRelation(null, Manager::SAVE));
        self::assertTrue(Manager::shouldSkipRelation([], Manager::SAVE));
        self::assertTrue(Manager::shouldSkipRelation(new stdClass, Manager::SAVE));

        // Checks if relation method exists
        self::assertTrue(Manager::shouldSkipRelation(new Ethereal, Manager::SAVE, new Ethereal, 'test'));

        // Valid
        self::assertFalse(Manager::shouldSkipRelation(new Ethereal, Manager::SAVE));
    }

    /**
     * @test
     */
    public function it_can_queue_before_action()
    {
        $manager = new RelationsManager(new Ethereal, []);

        self::assertCount(0, $manager->getBefore());

        $manager->before(function () {});

        self::assertCount(1, $manager->getBefore());
    }

    /**
     * @test
     */
    public function it_can_queue_after_action()
    {
        $manager = new RelationsManager(new Ethereal, []);

        self::assertCount(0, $manager->getAfter());

        $manager->after(function () {});

        self::assertCount(1, $manager->getAfter());
    }

    /**
     * @test
     */
    public function it_converts_array_options_to_collection()
    {
        $manager = new Manager(new Ethereal, []);

        self::assertInstanceOf(\Illuminate\Support\Collection::class, $manager->getOptions());
    }

    /**
     * @test
     */
    public function it_converts_relations_options_to_collection()
    {
        $manager = new Manager(new Ethereal, new \Illuminate\Support\Collection([
            'relations' => []
        ]));

        self::assertInstanceOf(\Illuminate\Support\Collection::class, $manager->getOptions()->get('relations'));
    }

    /**
     * @test
     */
    public function it_can_get_relation_options()
    {
        $manager = new Manager(new Ethereal, [
            'relations' => [
                'single' => Manager::SAVE,
                'single.nested' => Manager::DELETE,
            ]
        ]);

        self::assertEquals(Manager::SAVE, $manager->getRelationOptions('single'));
        self::assertEquals(Manager::DELETE, $manager->getRelationOptions('single.nested'));
    }

    /**
     * @test
     */
    public function it_can_check_if_nested_relation_will_be_skipped()
    {
        $manager = new Manager(new Ethereal, [
            'relations' => [
                'single' => Manager::SKIP,
                'single.multi' => Manager::SAVE,
            ]
        ]);

        self::assertEquals(Manager::SKIP, $manager->getRelationOptions('single.multi'));

        $manager = new Manager(new Ethereal, [
            'relations' => [
                'single' => Manager::SAVE | Manager::SKIP_RELATIONS,
                'single.multi' => Manager::SAVE,
            ]
        ]);

        self::assertEquals(Manager::SKIP, $manager->getRelationOptions('single.multi'));
    }

    /**
     * @test
     */
    public function it_can_skip_relations()
    {
        $manager = new Manager(new Ethereal, [
            'relations' => [
                'single' => Manager::SAVE,
                'single.nested' => Manager::SAVE | Manager::SKIP_RELATIONS,
                'single.nested.child' => Manager::SAVE,
            ]
        ]);

        self::assertEquals(Manager::SAVE, $manager->getRelationOptions('single'));
        self::assertEquals(Manager::SAVE | Manager::SKIP_RELATIONS, $manager->getRelationOptions('single.nested'));
        self::assertEquals(Manager::SKIP, $manager->getRelationOptions('single.nested.child'));
    }
}

class SoftDeletingClass
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
}

class RelationsManager extends Manager
{
    public function getBefore()
    {
        return $this->beforeParentSave;
    }

    public function getAfter()
    {
        return $this->afterParentSave;
    }
}
