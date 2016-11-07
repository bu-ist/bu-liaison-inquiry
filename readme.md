# BU Liaison Inquiry #
Inquiry form for Liaison Inc.'s SpectrumEMP enrollment CRM
## Description
This Wordpress plugin provides an inquiry form for prospective students.  It uses the SpectrumEMP API to get the form parameters from Liaison, and submit the form data back to Liaison. It is based on example code from `https://github.com/Liaison-Intl/EMP_API-Example`
## Usage
### Admin
The plugin provides an option page in the Wordpress admin, under the main `Settings` menu called `Liaison API Keys`.  Enter the API Key and Client ID provided by Liaison for the relevant account here.
### Inquiry Form shortcode
Once the API Key and Client ID have been set, the inquiry form can be placed anywhere in the site by using the following shortcode:

`[liaison_inquiry_form]`

When the page or post is displayed, the shortcode will be replaced by the Liaison iquiry form.  Prospective students can fill out the form and submit it directly from the Wordpress site, and will be redirected to their personal URL on the Spectrum EMP site.