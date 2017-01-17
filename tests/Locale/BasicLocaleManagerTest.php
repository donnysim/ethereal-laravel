<?php

use Ethereal\Locale\BasicLocaleManager;

class BasicLocaleManagerTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_sets_application_locale()
    {
        $manager = new BasicLocaleManager($this->app);
        $manager->setLocale('gb');

        self::assertEquals('gb', $manager->getLocale());
    }

    /**
     * @test
     */
    public function it_sets_application_fallback_locale()
    {
        $manager = new BasicLocaleManager($this->app);
        $manager->setFallbackLocale('fr');

        self::assertEquals('fr', $manager->getFallbackLocale());
    }

    /**
     * @test
     */
    public function it_gets_available_locales()
    {
        $manager = new BasicLocaleManager($this->app);

        self::assertEquals(['en'], $manager->getAvailableLocales());
    }
}
