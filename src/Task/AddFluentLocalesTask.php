<?php

namespace taoceanz\Dev;

use SilverStripe\Dev\BuildTask;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Dev\Debug;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Build task to create required Locale if they don't exist.
 *
 * Ensure all required Locale exist in the database
 */
class AddFluentLocalesTask extends BuildTask
{
    protected $title = "Add Fluent Locales Task";

    protected $description = "Ensures required locales exist else creates them.";

    private static $segment = 'AddFluentLocalesTask';

    private $_fluent_locales;

    private $_fluent_locales_path;

    /**
     * Init Fluent Locales Build Task
     * 
     * @return void
     */
    public function init()
    {
        $this->_setFluentLocalesPath('');
        $this->_fluent_locales = $this->_getFluentLocales();
    }

    /**
     * Run Add Fluent Locales Task
     *
     * Define locales then prompt their creation
     *
     * @param HTTPRequest $request Request
     * 
     * @return void
     */
    public function run($request)
    {
        // $locales = [
        //     [
        //         'Title' => "English (US)",
        //         'Locale' => "en_US",
        //         'URLSegment' => "en",
        //         'IsGlobalDefault' => 1,
        //     ],
        //     [
        //         'Title' => "中文",
        //         'Locale' => "zh_cmn",
        //         'URLSegment' => "cn",
        //         'IsGlobalDefault' => 0,
        //     ],
        // ];

        if (!$this->_isFluentLocalesValid()) {
            return;
        }

        foreach ($this->_fluent_locales as $locale) {
            $this->_createLocale(
                $locale['Title'],
                $locale['Locale'],
                $locale['URLSegment'],
                $locale['IsGlobalDefault']
            );
        }
    }

    /**
     * Read in fluent locale yml and parse to array
     *
     * @return Array $fluent_locales Fluent locale array
     */
    private function _getFluentLocales()
    {
        $fluent_locales = [];
        // read in yml path and parse into array

        try {
            $fluent_locales = Yaml::parseFile($this->_fluent_locales_path);
        } catch (ParseException $exception) {
            Debug::message("Yaml parse failure: '$exception'.", false);
        }

        return $fluent_locales;
    }

    /**
     * Define path for fluent locales yml file
     * 
     * @param String $path path to fluent locale yaml file
     *
     * @return void
     */
    private function _setFluentLocalesPath($path)
    {
        $this->_fluent_locales_path = $path;
    }

    /**
     * Check whether fluent locales array is valid
     *
     * @return Bool
     */
    private function _isFluentLocalesValid()
    {
        return 
            !is_array($this->_fluent_locales) ||
            empty($this->_fluent_locales);
    }

    /**
     * Create locale if it doesn't exist
     *
     * @param string $Title           Title of locale
     * @param string $Locale          Locale code
     * @param string $URLSegment      URL
     * @param int    $IsGlobalDefault Whether Locale is globale default
     *
     * @return void
     */
    private function _createLocale(
        $Title,
        $Locale,
        $URLSegment,
        $IsGlobalDefault
    ) {
        $locale_exists = Locale::get()
            ->filter(['Locale' => $Locale])
            ->count();

        if ($locale_exists) {
            Debug::message("Skipping: Locale exists: '$Locale'.", false);
            return;
        }

        $locale = Locale::create();

        $locale->Title = $Title;
        $locale->Locale = $Locale;
        $locale->URLSegment = $URLSegment;
        $locale->IsGlobalDefault = $IsGlobalDefault;

        $locale->write();

        Debug::message("Creating: Locale created: '$Locale'.", false);
    }
}
