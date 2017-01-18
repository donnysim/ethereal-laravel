<?php

namespace Ethereal\Database\Traits;

use Ethereal\Locale\LocaleManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property \Illuminate\Database\Eloquent\Collection|null translations
 */
trait Translatable
{
    /**
     * State if the model is translatable.
     *
     * @var bool|array
     */
    protected $translatable = false;

    /**
     * Translation model class.
     *
     * @var string|null
     */
    protected $translationModel;

    /**
     * Get state if the model is translatable.
     *
     * @return bool
     */
    public function translatable()
    {
        return $this->translatable !== false;
    }

    /**
     * Delete translations.
     *
     * @param array|null $locales
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function deleteTrans($locales = null)
    {
        if ($locales === null) {
            $this->translations()->delete();
            $this->setRelation('translations', new Collection());
        } else {
            $this->translations()->whereIn('locale', (array)$locales)->delete();
            $this->load('translations');
        }
    }

    /**
     * Translations relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany($this->translationModelClass(), 'model_id');
    }

    /**
     * Get translation model class.
     *
     * @return string
     */
    protected function translationModelClass()
    {
        return $this->translationModel ?: get_class($this) . 'Translation';
    }

    /**
     * Get translation or new translation instance.
     *
     * @param string $locale
     *
     * @return \Ethereal\Database\Ethereal
     */
    public function transOrNew($locale)
    {
        return $this->trans($locale, false) ?: $this->newTrans($locale);
    }

    /**
     * Get translation.
     *
     * @param string|null $locale
     * @param string|bool|null $fallback
     *
     * @return \Ethereal\Database\Ethereal|null
     */
    public function trans($locale = null, $fallback = null)
    {
        return $this->getTranslation($locale, $fallback);
    }

    /**
     * Get translation.
     *
     * @param string|null $locale
     * @param string|bool|null $fallback
     *
     * @return null
     */
    protected function getTranslation($locale = null, $fallback = null)
    {
        $locale = $locale ?: $this->localeManager()->getLocale();
        $fallbackLocale = $fallback ?: $this->localeManager()->getFallbackLocale();

        $fallbackTrans = null;

        if (!$this->exists && !$this->relationLoaded('translations')) {
            return null;
        }

        foreach ($this->translations as $translation) {
            if ($translation->getAttribute('locale') === $locale) {
                return $translation;
            }

            if ($translation->getAttribute('locale') === $fallbackLocale) {
                $fallbackTrans = $translation;
            }
        }

        if ($fallback === false) {
            return null;
        }

        return $fallbackTrans;
    }

    /**
     * Get locale manager.
     *
     * @return \Ethereal\Locale\LocaleManager
     */
    protected function localeManager()
    {
        return app(LocaleManager::class);
    }

    /**
     * Make new translation and add it to translations collection.
     *
     * @param string $locale
     * @param bool $addToCollection
     *
     * @return \Ethereal\Database\Ethereal
     */
    public function newTrans($locale, $addToCollection = true)
    {
        $class = $this->translationModelClass();
        $model = new $class(['locale' => $locale]);

        if ($addToCollection) {
            if (!$this->relationLoaded('translations')) {
                $this->setRelation('translations', new Collection());
            }

            $this->translations->add($model);
        }

        return $model;
    }

    /**
     * Determine if any of the translation models are dirty.
     *
     * @return bool
     */
    protected function translationsDirty()
    {
        if (!$this->relationLoaded('translations')) {
            return false;
        }

        foreach ($this->translations as $translation) {
            /** @var $translation \Illuminate\Database\Eloquent\Model */
            if ($translation->isDirty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attach current translation and fallback.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $locale
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeWithTranslationAndFallback(Builder $query, $locale = null)
    {
        $locale = $locale ?: $this->localeManager()->getLocale();
        $fallback = $this->localeManager()->getFallbackLocale();

        return $query->with([
            'translations' => function ($query) use ($locale, $fallback) {
                $query->whereIn('locale', array_unique([$locale, $fallback]));
            },
        ]);
    }
}