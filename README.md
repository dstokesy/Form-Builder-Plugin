# October CMS Form Builder Plugin

---
#### Authors
Author Daniel Stokes

---
## Installation Instructions

Run the following to install this plugin:

```bash
php artisan plugin:install Dstokesy.Forms --from=https://github.com/dstokesy/Form-Builder-Plugin
```

If you already have this plugin installed and need to update the database schema, run this command:

```bash
php artisan plugin:refresh Dstokesy.Forms
```

To uninstall this plugin:

```bash
php artisan plugin:remove Dstokesy.Forms
```

---
## Forms
To add a form simply add the component to a page a select the form.

---
## Entries
Entries a filled in by the form component and the data from the form is stored in the jsonable field data. They are also related to a user and the url of the page that the form was submitted from is recorded.
Entries for each form are split up into seperate backend navigation items and an unread count is displayed next to each menu item.
A dashboard widget can also be added showing the number of unread entries
When opended an entry is marked as read, but this can be overwritten by ticking mark as unread. Entries cna also be marked as dealt with and the user and date as which the entry is dealt with is recorded.

---
## Recaptcha
To enable Google's ReCaptcha copy the plugins config file into the sites config directory and enabled and fill in keys.
    
Keys can be obtained by adding the site to https://www.google.com/recaptcha/admin/
    
Next uncomment the below code in your app.js file
```javascript
require('./library/Request.js');
```
   
Finally add the following to footer tags

```html
<script src="//google.com/recaptcha/api.js?render={{ config('dstokesy.forms::recaptcha.siteKey') }}&onload=onloadCallback" async defer></script>

<script>
var onloadCallback = function() {
    window.site.generateRecaptchaToken();
};	
</script>
```