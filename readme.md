# BU Liaison Inquiry #
Inquiry form for Liaison Inc.'s SpectrumEMP enrollment CRM
## Description
This WordPress plugin provides an inquiry form for prospective students.  It uses the SpectrumEMP API to get the form parameters from Liaison, and submit the form data back to Liaison. It is based on example code from `https://github.com/Liaison-Intl/EMP_API-Example`
## Basic Usage
### Admin
The plugin provides an option page in the WordPress admin, under the main `Settings` menu called `Liaison API Keys`.  Enter the API Key and Client ID provided by Liaison for the relevant account here.
### Inquiry Form shortcode
Once the API Key and Client ID have been set, the inquiry form can be placed anywhere in the site by using the following shortcode:

`[liaison_inquiry_form]`

When the page or post is displayed, the shortcode will be replaced by the Liaison inquiry form.  Prospective students can fill out the form and submit it directly from the WordPress site, and will be redirected to their personal URL on the Spectrum EMP site.
## Advanced Usage
### Mini-form

A mini-form can be created by adding a shortcode attribute named `fields` containing a comma delimited list of integer field ids.  The field ids that are listed will appear in the shortened form.  

* Any unlisted fields that are not required will be dropped from the form
* Any unlisted required fields with no preset values set in the shortcode will be included as hidden field with a default value (currently `mini-form`)
* Any unlisted required fields that have a preset value set in the shortcode will be included as a hidden field with the preset value

Preset values can be added to the shortcode by adding an attribute with the field id and value like this: `11="PN"`.  Here `11` is the field id for the Country, and `PN` is a country code that will be used as the preset value.

### Arbitrary preset values
Any other values can be set by including a shortcode attribute of the form `field_id="preset value"`.  As long as there is a valid field id, any field can be preset in this way regardless of whether the field is part of the inquiry form.

### SOURCE
Liaison uses a special field called `source` that can track where a lead originated.  It appears to be the only field in the Liaison forms that uses something other than an integer for the field id.  The source can be set in a shortcode attribute like any other field like this: `source="12345"`.

## Dev Mode

The plugin may be switched to dev mode. In this mode, no requests to the SpectrumEMP API will be sent. It is useful mostly for developers working on new features, but also for plugin users who want to try the plugin out prior obtaining Liaison API Keys.

To switch to dev mode, add the following to `wp-config.php`:

```php
define('BU_LIAISON_INQUIRY_MOCK', true);
```
