<?php

namespace Vulcan\Search\Tests;

use SilverStripe\Dev\FunctionalTest;
use Vulcan\Search\Extensions\SearchIndexExtension;

/**
 * Class SearchIndexExtensionTest
 * @package Vulcan\Search\Tests
 */
class SearchIndexExtensionTest extends FunctionalTest
{
    public function setUp()
    {
        parent::setUp();

        \Page::add_extension(SearchIndexExtension::class);
    }

    public function tearDown()
    {
        parent::tearDown();

        \Page::remove_extension(SearchIndexExtension::class);
    }

    /**
     * @covers SearchIndexExtension
     */
    public function testExtensionMethods()
    {
        /** @var SearchIndexExtension $singleton */
        $singleton = singleton(SearchIndexExtension::class);
        $this->assertEquals(['Title', 'Content'], $singleton->searchableColumns(), 'Default searchable columns should be "Title" and "Content"');

        $classesWithExtension = SearchIndexExtension::extendedClasses();
        $this->assertTrue(in_array(\Page::class, $classesWithExtension), 'The "Page" class should be currently found in SearchIndexExtension::extendedClasses() but is not');
    }
}