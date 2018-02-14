<?php

namespace Vulcan\Search\Models;

use SilverStripe\ORM\DataObject;

/**
 * Class SortFilter
 * @package Vulcan\Search\Models
 *
 * @property string Title
 * @property string SortSql
 *
 * @property int PageID
 * @method \Page Page
 */
class SortFilter extends DataObject
{
    private static $table_name = 'VulcanSortFilter';

    private static $db = [
        'Title' => 'Varchar(255)',
        'SortSql' => 'Varchar(255)'
    ];

    private static $has_one = [
        'Page' => \Page::class
    ];
}