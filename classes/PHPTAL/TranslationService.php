<?php

interface PHPTAL_TranslationService
{
    /**
     * Set the target language for translations.
     *
     * When set to '' no translation will be done.
     */
    function setLanguage($langCode);

    /**
     * Set the domain to use for translations.
     */
    function setDomain($domain);

    /**
     * Set an interpolation var.
     */
    function setVar($key, $value);

    /**
     * Translate a gettext key and interpolate variables.
     */
    function translate($key);
}

?>
