<?php

namespace OliverNorden\HeadlessRest;

use SilverStripe\ORM\DataList;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\View\Parsers\ShortcodeParser;

class DataObjectExtension extends Extension {

    public function getCacheKey() {
        if ($this->owner instanceof SiteTree) {
            return 'page-' . $this->owner->ID;
        }
    }

    public function clearCache() {
        $cache = Injector::inst()->get(CacheInterface::class . HeadlessRestController::CACHE_NAMESPACE);

        // Save current stage and set stage to live
        // Used to clear live cache
        $beforeStage = Versioned::get_stage();
        Versioned::set_stage(Versioned::LIVE);

        // Clear cache of affected pages
        foreach(SiteTree::get() as $page) {
            // Check if page cache should be cleared based on changed page
            if (!$page->shouldClearCache($this->owner)) continue;

            $cacheKey = $page->getCacheKey();
            $cache->delete($cacheKey);
        }

        // Clear page cache
        $cacheKey = $this->owner->getCacheKey();
        $cache->delete($cacheKey);
        $cache->delete('common');
        $cache->delete('sitetree');

        // Reset versioned stage
        Versioned::set_stage($beforeStage);
        return true;
    }

    public function getHeadlessRestFields($fields = null) {
        // Add fields from config if none are passed as argument
        $fields = $fields ? $fields : $this->owner->config()->headlessFields;

        // Check if configured fields exist
        if (!$fields) return null;

        foreach ($fields as $label => $name) {
            if ($this->owner->hasDatabaseField($name)) {
                $field = $this->owner->dbObject($name);

                // Parse shortcodes of DBHTMLText and DBHTMLVarchar
                $fields[$label] = ($field instanceof DBHTMLText || $field instanceof DBHTMLVarchar)
                    ? ShortcodeParser::get_active()->parse($field->getValue())
                    : $field->getValue();
            }
            // Recursively get headless fields for relations, and values from methods
            else {
                $objectListOrMethod = $this->owner->$name();
                
                // Many many, Data list or has many
                if (
                    $objectListOrMethod instanceof DataList ||
                    $objectListOrMethod instanceof ManyManyList ||
                    $objectListOrMethod instanceof HasManyList
                ) {
                    $fields[$label] = [];
                    foreach ($objectListOrMethod as $object) {
                        array_push($fields[$label], $object->getHeadlessRestFields());
                    }
                }
                // Methods or has one
                else {
                    $fields[$label] = $objectListOrMethod instanceof DataObject ? 
                        $objectListOrMethod->getHeadlessRestFields():
                        $objectListOrMethod;
                }
            }
        }
        return $fields;
    }

    public function shouldClearCache($dataObject) {
        return true;
    }

    public function onAfterPublish(&$original) {
        $this->owner->clearCache();
    }

    public function onAfterUnpublish() {
        $this->owner->clearCache();
    }
}