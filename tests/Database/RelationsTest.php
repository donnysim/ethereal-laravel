<?php

use Ethereal\Database\Ethereal;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;

class RelationsTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'ethereal',
            '--realpath' => __DIR__ . '/../../migrations'
        ]);
    }

    /**
     * Setup testing environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'ethereal');
        $app['config']->set('database.connections.ethereal', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBelongsToManyIsSetProperly()
    {
        $model = new RelationsBaseStub;

        // Empty array should be set as sync array
        $model->setRelation('btmRelation', []);
        static::assertTrue($model->relationLoaded('btmRelation'));
        static::assertTrue(is_array($model->getRelation('btmRelation')));

        // Array object should be converted into collection with model object
        $model->setRelation('btmRelation', ['slug' => 'users.index', 'title' => 'Users list']);
        static::assertTrue($model->relationLoaded('btmRelation'));

        /** @var Collection $collection */
        $collection = $model->getRelation('btmRelation');
        static::assertInstanceOf(Collection::class, $collection);
        static::assertEquals(1, $collection->count());

        $first = $collection->first();
        static::assertInstanceOf(Ethereal::class, $first);
        static::assertEquals(['slug' => 'users.index', 'title' => 'Users list'], $first->toArray());

        static::assertFalse(false, $first->exists);

        $model->setRelation('btmRelation', ['id' => 1, 'slug' => 'users.index', 'title' => 'Users list']);
        static::assertTrue($model->getRelation('btmRelation')->first()->exists);

        // Empty values should remove relation
        $model->setRelation('btmRelation', null);
        static::assertFalse($model->relationLoaded('btmRelation'));

        // Settings relation to model should wrap into collection
        $model->setRelation('btmRelation', new RelationsRolesStub);
        static::assertInstanceOf(Collection::class, $model->getRelation('btmRelation'));
        static::assertEquals(1, $model->getRelation('btmRelation')->count());

        // Settings relation to collection should keep it as is
        $model->setRelation('btmRelation', new Collection([new RelationsRolesStub]));
        static::assertInstanceOf(Collection::class, $model->getRelation('btmRelation'));
        static::assertEquals(1, $model->getRelation('btmRelation')->count());

        // Should prevent from settings relation with invalid models TODO check all items
        $model->setRelation('btmRelation', new Collection([new RelationsBaseStub]));
    }

    public function testBelongsToManyPush()
    {
        $model = new RelationsBaseStub;
        $model->setAttribute('email', 'ethereal@laravel.test');
        $model->setAttribute('password', 'dummy-text');

        $relation = new RelationsRolesStub;
        $relation->setAttribute('slug', 'user.index');

        $model->setRelation('btmRelation', $relation);
        static::assertTrue($model->smartPush());

        // Relation should be saved and linked to parent
        static::assertTrue(DB::table('roles')->where('slug', 'user.index')->exists());
        static::assertTrue(DB::table('role_user')->where('role_id', $relation->getKey())->where('user_id', $model->getKey())->exists());

        // Resaving should have no effect
        static::assertTrue($model->smartPush());
        static::assertEquals(1, DB::table('roles')->where('slug', 'user.index')->count());
        static::assertEquals(1, DB::table('role_user')->where('role_id', $relation->getKey())->where('user_id', $model->getKey())->count());

        // Resaving should successfully update model value
        $relation->setAttribute('slug', 'user.edit');
        static::assertTrue($model->smartPush());
        static::assertFalse(DB::table('roles')->where('slug', 'user.index')->exists());
        static::assertEquals(1, DB::table('roles')->where('slug', 'user.edit')->count());

        // Detaching option should remove the relation
        static::assertTrue($model->smartPush(['relations' => ['btmRelation' => Ethereal::OPTION_DETACH]]));
        static::assertFalse(DB::table('role_user')->where('role_id', $relation->getKey())->where('user_id', $model->getKey())->exists());

        // Simple sync array should be saved normally
        $model->setRelation('btmRelation', [1, 2, 3]);
        static::assertTrue($model->smartPush());
        static::assertEquals(3, DB::table('role_user')->where('user_id', $model->getKey())->count());

        // Test save and detach on sync array, save should not be executed
        $model->setRelation('btmRelation', [2]);
        static::assertTrue($model->smartPush(['relations' => ['btmRelation' => Ethereal::OPTION_SAVE | Ethereal::OPTION_DETACH]]));
        static::assertEquals(2, DB::table('role_user')->where('user_id', $model->getKey())->count());

        // Test sync ability to clear all relations
        $model->setRelation('btmRelation', []);
        static::assertTrue($model->smartPush(['relations' => ['btmRelation' => Ethereal::OPTION_SYNC]]));
        static::assertEquals(0, DB::table('role_user')->where('user_id', $model->getKey())->count());

        // Test mixed array
        $model->setRelation('btmRelation', [$relation, ['slug' => 'user.create']]);
        static::assertTrue($model->smartPush());
        static::assertEquals(2, DB::table('role_user')->where('user_id', $model->getKey())->count());
        $model->btmRelation()->sync([]);

        $model->setRelation('btmRelation', [['slug' => 'user.create'], $relation]);
        static::assertTrue($model->smartPush());
        static::assertEquals(2, DB::table('role_user')->where('user_id', $model->getKey())->count());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testHasManyIsSetProperly()
    {
        $model = new RelationsBaseStub;

        // Empty array should be set as sync array
        $model->setRelation('hmRelation', []);
        static::assertTrue($model->relationLoaded('hmRelation'));
        static::assertTrue(is_array($model->getRelation('hmRelation')));

        // Array object should be converted into collection with model object
        $model->setRelation('hmRelation', ['name' => 'Ethereal', 'last_name' => 'Laravel']);
        static::assertTrue($model->relationLoaded('hmRelation'));

        /** @var Collection $collection */
        $collection = $model->getRelation('hmRelation');
        static::assertInstanceOf(Collection::class, $collection);
        static::assertEquals(1, $collection->count());

        $first = $collection->first();
        static::assertInstanceOf(Ethereal::class, $first);
        static::assertEquals(['name' => 'Ethereal', 'last_name' => 'Laravel'], $first->toArray());

        static::assertFalse(false, $first->exists);

        $model->setRelation('hmRelation', ['id' => 1, 'name' => 'Ethereal', 'last_name' => 'Laravel']);
        static::assertTrue($model->getRelation('hmRelation')->first()->exists);

        // Empty values should remove relation
        $model->setRelation('hmRelation', null);
        static::assertFalse($model->relationLoaded('hmRelation'));

        // Settings relation to model should wrap into collection
        $model->setRelation('hmRelation', new RelationsRolesStub);
        static::assertInstanceOf(Collection::class, $model->getRelation('hmRelation'));
        static::assertEquals(1, $model->getRelation('hmRelation')->count());

        // Settings relation to collection should keep it as is
        $model->setRelation('hmRelation', new Collection([new RelationsProfilesStub]));
        static::assertInstanceOf(Collection::class, $model->getRelation('hmRelation'));
        static::assertEquals(1, $model->getRelation('hmRelation')->count());

        // Should prevent from settings relation with invalid models TODO check all items
        $model->setRelation('hmRelation', new Collection([new RelationsBaseStub]));
    }

    public function testHasManyPush()
    {
        $model = new RelationsBaseStub;
        $model->setAttribute('email', 'ethereal@laravel.test');
        $model->setAttribute('password', 'dummy-text');

        $relation = new RelationsProfilesStub;
        $relation->setAttribute('name', 'My Name');
        $relation->setAttribute('last_name', 'My Last Name');

        $model->setRelation('hmRelation', $relation);
        static::assertTrue($model->smartPush());

        // Relation should be saved and linked to parent
        static::assertTrue(DB::table('profiles')->where('user_id', $model->getKey())->exists());
        static::assertTrue(DB::table('profiles')->where('user_id', $model->getKey())->where('name', 'My Name')->where('last_name', 'My Last Name')->exists());

        // Resaving should have no effect
        static::assertTrue($model->smartPush());
        static::assertEquals(1, DB::table('profiles')->where('user_id', $model->getKey())->count());

        // Resaving should successfully update model value
        $relation->setAttribute('name', 'New Name');
        static::assertTrue($model->smartPush());
        static::assertTrue(DB::table('profiles')->where('user_id', $model->getKey())->where('name', 'New Name')->exists());

        // Delete option should remove the item from relation
        static::assertTrue($model->smartPush(['relations' => ['hmRelation' => Ethereal::OPTION_DELETE]]));
        static::assertFalse(DB::table('profiles')->where('user_id', $model->getKey())->exists());
        static::assertEquals(0, $model->getRelation('hmRelation')->count());

        // Test mixed array
        $model->setRelation('hmRelation', [$relation, ['name' => 'N', 'last_name' => 'LN']]);
        static::assertTrue($model->smartPush());
        static::assertEquals(2, DB::table('profiles')->where('user_id', $model->getKey())->count());
    }

    public function testHasOneIsSetProperly()
    {
        $model = new RelationsBaseStub;

        // Empty array should be set as sync array
        $model->setRelation('hoRelation', []);
        static::assertTrue($model->relationLoaded('hoRelation'));

        // Array object should be converted into model object
        $model->setRelation('hoRelation', ['name' => 'Ethereal', 'last_name' => 'Laravel']);
        static::assertTrue($model->relationLoaded('hoRelation'));
        static::assertInstanceOf(Ethereal::class, $model->getRelation('hoRelation'));

        $model->setRelation('hoRelation', ['id' => 1, 'name' => 'Ethereal', 'last_name' => 'Laravel']);
        static::assertTrue($model->getRelation('hoRelation')->exists);

        // Empty values should remove relation
        $model->setRelation('hoRelation', null);
        static::assertFalse($model->relationLoaded('hoRelation'));

        // Settings relation to collection should keep it as is
        $model->setRelation('hoRelation', new RelationsProfilesStub);
        static::assertInstanceOf(Ethereal::class, $model->getRelation('hoRelation'));
    }

    public function testHasOnePush()
    {
        $model = new RelationsBaseStub;
        $model->setAttribute('email', 'ethereal@laravel.test');
        $model->setAttribute('password', 'dummy-text');

        $relation = new RelationsProfilesStub;
        $relation->setAttribute('name', 'My Name');
        $relation->setAttribute('last_name', 'My Last Name');

        $model->setRelation('hoRelation', $relation);
        static::assertTrue($model->smartPush());

        // Relation should be saved and linked to parent
        static::assertTrue(DB::table('profiles')->where('user_id', $model->getKey())->exists());
        static::assertTrue(DB::table('profiles')->where('user_id', $model->getKey())->where('name', 'My Name')->where('last_name', 'My Last Name')->exists());

        // Resaving should have no effect
        static::assertTrue($model->smartPush());
        static::assertEquals(1, DB::table('profiles')->where('user_id', $model->getKey())->count());

        // Resaving should successfully update model value
        $relation->setAttribute('name', 'New Name');
        static::assertTrue($model->smartPush());
        static::assertTrue(DB::table('profiles')->where('user_id', $model->getKey())->where('name', 'New Name')->exists());

        // Delete option should remove the item from relation
        static::assertTrue($model->smartPush(['relations' => ['hoRelation' => Ethereal::OPTION_DELETE]]));
        static::assertFalse(DB::table('profiles')->where('user_id', $model->getKey())->exists());
        static::assertEquals(0, $model->getRelation('hoRelation')->count());
    }
}

class RelationsBaseStub extends Ethereal
{
    protected $table = 'users';

    protected $guarded = [];

    public function btmRelation()
    {
        return $this->belongsToMany(RelationsRolesStub::class, 'role_user', 'user_id', 'role_id');
    }

    public function hmRelation()
    {
        return $this->hasMany(RelationsProfilesStub::class, 'user_id', 'id');
    }

    public function hoRelation()
    {
        return $this->hasOne(RelationsProfilesStub::class, 'user_id', 'id');
    }
}

class RelationsProfilesStub extends Ethereal
{
    protected $table = 'profiles';

    protected $guarded = [];
}

class RelationsRolesStub extends Ethereal
{
    protected $table = 'roles';

    protected $guarded = [];
}
