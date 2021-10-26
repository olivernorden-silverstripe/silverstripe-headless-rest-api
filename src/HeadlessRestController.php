<?php

namespace OliverNorden\HeadlessRest;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Versioned\Versioned;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Injector\Injector;

class HeadlessRestController extends Controller {

    const CACHE_NAMESPACE = '.headless-rest';

    private static $url_handlers = array(
        '$action!/$*' => 'index',
    );

    private static $allowed_actions = array(
        'index',
        'notFound'
    );

    public function index(HTTPRequest $request) {

        Versioned::choose_site_stage($request);
        $action = $request->param('action');
        $cache = Injector::inst()->get(CacheInterface::class . self::CACHE_NAMESPACE);

        $this->extend('beforeHeadlessRestControllerAction', $request);

        switch ($action) {
            case 'url':
                $url = $request->remaining();
                $this->extend('updateUrl', $url);
                $page = SiteTree::get_by_link($url);

                $this->extend('updatePage', $page);
                if (!$page) {
                    return $this->notFound('Page not found ' . $url);
                }

                $cacheKey = $page->getCacheKey();
                $this->extend('updatePageCacheKey', $cacheKey);
                // Return cached page if exists
                if ($cacheKey && $cache->has($cacheKey) && Versioned::get_stage() === Versioned::LIVE) {
                    return $this->returnJson($cache->get($cacheKey));
                }

                // Save cache
                $pageData = $page->HeadlessRestFields;
                $cache->set($cacheKey, $pageData);
                return $this->returnJson($pageData);

                break;
            
            case 'common':
                $cacheKey = 'common';
                $this->extend('updateCommonCacheKey', $cacheKey);
                // Return cached fields if cache exists
                if ($cache->has($cacheKey) && Versioned::get_stage() === Versioned::LIVE) {
                    return $this->returnJson($cache->get($cacheKey));
                }

                $commonFields = $this->config()->headlessCommonFields;
                $fields = [];

                // Navigation              
                $navigationPages = SiteTree::get()->filter([
                    'ShowInMenus' => 1,
                    'ParentID' => 0,
                ]);
                $this->extend('updateNavigationPages', $navigationPages);
                $navFields = $commonFields['navigation']['fields'];
                $fields['Navigation'] = $this->getNavigationFields($navigationPages, $navFields);

                // Site config
                $sc = SiteConfig::current_site_config();
                $scField = $commonFields['siteConfig']['fields'];
                $fields['siteConfig'] = $sc->getHeadlessRestFields($scField);

                $cache->set($cacheKey, $fields);
                return $this->returnJson($fields);
                break;
            case 'sitetree':
                $cacheKey = 'sitetree';
                $this->extend('updateSiteTreeCacheKey', $cacheKey);
                // Return cached fields if cache exists
                if ($cache->has($cacheKey) && Versioned::get_stage() === Versioned::LIVE) {
                    return $this->returnJson($cache->get($cacheKey));
                }

                $siteTreeFields = $this->config()->headlessSiteTreeFields;
                $fields = [];

                $pages = SiteTree::get();
                $fields['siteTree'] = $this->getSiteTreeFields($pages, $siteTreeFields);

                $cache->set($cacheKey, $fields);
                return $this->returnJson($fields);
                break;
            
            default:
                // TODO: add support for extending with custom actions
                return $this->notFound();
                break;
        }
    }
    // Recursively itterate site tree pages to get fields and children
    private function getSiteTreeFields($pages, $fields) {
        if (!$pages->Count()) return null;

        $sitetree = [];

        foreach ($pages as $page) {
            $pageFields = $page->getHeadlessRestFields($fields);
            $childPages = $page->Children();
            $pageFields['children'] = $this->getSiteTreeFields($childPages, $fields);
            $sitetree[] = $pageFields;
        }

        return $sitetree;
    }

    // Recursively itterate site tree menu pages to get fields and children
    private function getNavigationFields($pages, $fields) {
        $navigation = [];

        if (!$pages->Count()) return $navigation;

        foreach ($pages as $page) {
            $pageFields = $page->getHeadlessRestFields($fields);
            $childNavPages = $page->Children()->filter(['ShowInMenus' => 1]);
            $pageFields['menuChildren'] = $this->getNavigationFields($childNavPages, $fields);
            $navigation[] = $pageFields;
        }

        return $navigation;
    }

    protected function returnJson($json) {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        return json_encode($json);
    }

    protected function notFound($msg = 'Something could not be found')
    {
        $this->getResponse()->setStatusCode(404);
        $this->getResponse()->addHeader('Content-Type', 'text/plain');
        return $msg;
    }

}
