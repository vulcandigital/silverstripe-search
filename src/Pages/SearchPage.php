<?php

namespace Vulcan\Search\Pages;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\HasManyList;
use Vulcan\Search\Models\SearchTank;
use Vulcan\Search\Models\SortFilter;

/**
 * Class SearchPage
 * @package Vulcan\Search\Pages
 *
 * @property int TankID
 *
 * @method SearchTank Tank
 * @method HasManyList Filters
 */
class SearchPage extends \Page
{
    private static $has_one = [
        'Tank' => SearchTank::class
    ];

    private static $has_many = [
        'Filters' => SortFilter::class
    ];

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (!$this->Filters()->exists()) {
            $record = SortFilter::create();
            $record->Title = 'Newest';
            $record->SortSql = 'Created DESC';
            $record->PageID = $this->ID;
            $record->write();

            $record = SortFilter::create();
            $record->Title = 'Oldest';
            $record->SortSql = 'Created ASC';
            $record->PageID = $this->ID;
            $record->write();
        }
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Search', [
            DropdownField::create('TankID', 'Tank', SearchTank::get()->map()),
            GridField::create('Filters', 'Filters', $this->Filters(), GridFieldConfig_RecordEditor::create())
        ]);

        return $fields;
    }
}