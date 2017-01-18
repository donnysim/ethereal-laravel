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

        //TODO
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
}

class TranslatableEthereal extends Ethereal
{
    protected $translatable = true;
}

class TranslatableEtherealTranslation extends Ethereal
{

}
