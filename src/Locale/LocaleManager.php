<?php

namespace Ethereal\Locale;

interface LocaleManager
{
    /**
     * Set application locale.
     *
     * @param string $locale
     */
    public function setLocale($locale);

    /**
     * Get application locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set application fallback locale.
     *
     * @param string $locale
     */
    public function setFallbackLocale($locale);

    /**
     * Get application locale.
     *
     * @return string
     */
    public function getFallbackLocale();

    /**
     * Get available locales list.
     *
     * @return array
     */
    public function getAvailableLocales();
}
