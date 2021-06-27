# Silverstripe CMS Headless rest api

This module adds a customizable rest api to Silverstripe for usage with front end frameworks such as Nuxt or Next JS. It adds three end points for fetching navigation and other common fields, site tree and url specific fields.

## Requirements

* SilverStripe ^4.0

## Installation
Add the following section to your composer.json
```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/olivernorden-silverstripe/silverstripe-headless-rest-api"
        }
    ],

```
Run

```
composer require olivernorden/silverstripe-headless-rest
```

**Note:** When you have completed your module, submit it to Packagist or add it as a VCS repository to your
project's composer.json, pointing to the private repository URL.
    
## Default api end points
1. `/headless-api/v1/url/<url>` will return all fields pertaining to <url>.
1. `/headless-api/v1/common` will return all fields pertaining to navigation and other common fields.
1. `/headless-api/v1/sitetree` will return all pages with their fields.

## Adding more fields to api
You can add more fields to any of the api end points.

### /headless-api/v1/url/<url>
```yaml
#app/_config/headless-fields.yml
---
Name: custom-headless-rest-fields
---
SilverStripe\CMS\Model\SiteTree:
  headlessFields:
    Title: Title
    Content: Content
    ClassName: ClassName
  
```
 
### /headless-api/v1/common
```yaml
#app/_config/headless-common-fields.yml
---
Name: custom-headless-rest-common-fields
---
OliverNorden\HeadlessRest\HeadlessRestController:
    headlessCommonFields:
        Navigation:
            fields:
                Lead: Lead
        SiteConfig:
            fields:
                TagLine: TagLine
```
 
### /headless-api/v1/sitetree
```yaml
#app/_config/headless-sitetree-fields.yml
---
Name: custom-headless-rest-sitetree-fields
---
OliverNorden\HeadlessRest\HeadlessRestController:
    headlessSiteTreeFields:
      AbsoluteURL: getAbsoluteURL
```

## Maintainers
 * Oliver Nordén <oliver@tapirens.se>
 
## Bugtracker
Bugs are tracked in the issues section of this repository. Before submitting an issue please read over 
existing issues to ensure yours is unique. 
 
If the issue does look like a new bug:
 
 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots 
 and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version, 
 Operating System, any installed SilverStripe modules.
 
Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.
 
## Development and contribution
If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.
