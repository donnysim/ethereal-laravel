<?php

namespace Ethereal\Locale;

use Illuminate\Foundation\Application;

class BasicLocaleManager implements LocaleManager
{
    /**
     * Main application.
     *
     * @type \Illuminate\Foundation\Application
     */
    protected $application;

    /**
     * BasicLocaleManager constructor.
     *
     * @param \Illuminate\Foundation\Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Set application locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->application->setLocale($locale);
    }

    /**
     * Get application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->application->getLocale();
    }

    /**
     * Set application fallback locale.
     *
     * @param string $locale
     */
    public function setFallbackLocale($locale)
    {
        $this->application->make('config')->set('app.fallback_locale', $locale);
    }

    /**
     * Get application locale.
     *
     * @return string
     */
    public function getFallbackLocale()
    {
        return $this->application->make('config')->get('app.fallback_locale', $this->getLocale());
    }

    /**
     * Get available locales list.
     *
     * @return array
     */
    public function getAvailableLocales()
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->application->make('config');

        return $config->get('app.locales', array_unique([$config->get('app.locale'), $config->get('fallback_locale', $config->get('app.locale'))]));
    }
}
