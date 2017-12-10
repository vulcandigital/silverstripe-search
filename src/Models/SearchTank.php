<?php

namespace Vulcan\Search\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use Vulcan\Search\Extensions\SearchIndexExtension;

/**
 * Class SearchTank
 * @package Vulcan\Search\Models
 *
 * @property string                         Title
 *
 * @property HasManyList|SearchIndexEntry[] Index
 */
class SearchTank extends DataObject
{
    private static $db = [
        'Title' => 'Varchar(255)'
    ];

    private static $has_many = [
        'Index' => SearchIndexEntry::class
    ];

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        if (!static::get()->filter('Title', 'Main')->first()) {
            $record = static::create();
            $record->Title = 'Main';
            $record->write();
        }
    }

    /**
     * @param $tank
     *
     * @return DataObject|static
     */
    public static function findOrCreateTank($tank)
    {
        $record = static::get()->filter('Title', $tank)->first();

        if ($record) {
            return $record;
        }

        $record = static::create();
        $record->Title = $tank;
        $record->write();

        return $record;
    }

    /**
     * Returns an array of all classes that are using the given tank name
     * .
     *
     * @param $tank
     *
     * @return array
     */
    public static function classesImplementTank($tank)
    {
        if ($tank instanceof SearchTank) {
            $tank = $tank->Title;
        }

        $classes = SearchIndexExtension::extendedClasses();
        $classesWithTank = [];

        foreach ($classes as $class) {
            if (singleton($class)->config()->get('search_tank') == $tank) {
                $classesWithTank[] = $class;
            }
        }

        return $classesWithTank;
    }
}