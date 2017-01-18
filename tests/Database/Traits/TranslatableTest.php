<?php

use Ethereal\Database\Ethereal;
use Ethereal\Locale\BasicLocaleManager;
use Ethereal\Locale\LocaleManager;

class TranslatableTest extends BaseTestCase
{
    use UsesDatabase;

    protected function setUp()
    {
        parent::setUp();

        $this->app->singleton(LocaleManager::class, function () {
            return new BasicLocaleManager($this->app);
        });
    }

    /**
     * @test
     */
    public function it_checks_if_translations_are_enabled()
    {
        $model = new Ethereal;

        self::assertFalse($model->translatable());
    }

    /**
     * @test
     */
    public function it_deletes_translations()
    {
        $this->migrate();

        $model = new TranslatableEthereal;
        $model->newTrans('gb');

        $model->smartPush();
        self::assertEquals(1, $model->translations()->count());

        $model->deleteTrans();
        self::assertEquals(0, $model->translations()->count());
    }

    /**
     * @test
     */
    public function it_doesnt_query_database_if_model_does_not_exist()
    {
        $model = new TranslatableEthereal;
        $model->transOrNew('en');

        self::assertEquals(1, $model->translations->count());
    }

    /**
     * @test
     */
    public function it_deletes_translations_by_locale()
    {
        $this->migrate();

        $model = new TranslatableEthereal;
        $model->newTrans('gb');
        $model->newTrans('en');

        $model->smartPush();
        self::assertEquals(2, $model->translations()->count());

        $model->deleteTrans('en');
        self::assertEquals(1, $model->translations()->count());
    }

    /**
     * @test
     */
    public function it_makes_new_translation_model_and_adds_to_collection()
    {
        $model = new TranslatableEthereal;
        $translation = $model->newTrans('gb');

        self::assertInstanceOf(TranslatableEtherealTranslation::class, $translation);
        self::assertEquals('gb', $translation->locale);
        self::assertEquals('gb', $model->translations->first()->locale);
    }

    /**
     * @test
     */
    public function it_can_get_translation_by_locale_and_returns_null_if_not_found()
    {
        $model = new TranslatableEthereal;
        $model->setRelation('translations', [$model->newTrans('gb')]);
        $translation = $model->trans('gb');

        self::assertInstanceOf(TranslatableEtherealTranslation::class, $translation);
        self::assertFalse($translation->exists);
        self::assertEquals('gb', $translation->locale);

        $translation = $model->trans('en');
        self::assertNull($translation);
    }

    /**
     * @test
     */
    public function it_can_get_translation_by_locale_or_return_new_model()
    {
        $model = new TranslatableEthereal;
        $model->setRelation('translations', [$model->newTrans('gb')]);
        $translation = $model->transOrNew('gb');

        self::assertInstanceOf(TranslatableEtherealTranslation::class, $translation);
        self::assertFalse($translation->exists);
        self::assertEquals('gb', $translation->locale);

        $translation = $model->transOrNew('en');
        self::assertInstanceOf(TranslatableEtherealTranslation::class, $translation);
        self::assertEquals('en', $translation->locale);
    }

    /**
     * @test
     */
    public function it_has_with_translation_and_fallback_scope()
    {
        $this->migrate();

        $model = TranslatableEthereal::create();
        $model->transOrNew('en');
        $model->transOrNew('gb');
        $model->smartPush();

        $this->app->make(LocaleManager::class)->setLocale('en');
        $this->app->make(LocaleManager::class)->setFallbackLocale('fr');

        self::assertEquals(1, TranslatableEthereal::withTranslationAndFallback()->find($model->getKey())->translations->count());

        $model->transOrNew('fr');
        $model->smartPush();

        self::assertEquals(2, TranslatableEthereal::withTranslationAndFallback()->find($model->getKey())->translations->count());
    }
}

class TranslatableEthereal extends Ethereal
{
    protected $table = 'articles';

    protected $translatable = [];
}

class TranslatableEtherealTranslation extends Ethereal
{
    protected $table = 'articles_translations';

    public $timestamps = false;
}
