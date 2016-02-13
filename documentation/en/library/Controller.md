# Controller

* [About](#about)
* [Properties](#properties)
* [Methods](#methods)

## About

Controller is a simple class you can extend to create controllers for your application.

## Properties

All properties are *protected* and can be changed by child classes.

|Property|Type|Description|Default Value|
|---|---|---|---|
|**$authNeeded**|*boolean*|Define if the controller is restricted to signed in users.|*false*|
|**$redirectUnsigned**|*mixed*|Define a URL to redirect the user if it is not signed ($authNeeded must be true). Can be a string or an array user by URI::buildUrl();|*false|
|**$signedUser**|*object*|The current user signed in object.|*null*|
|**$tplIsChaced**|*boolean*|Define if the template's page must be cached.|*false*|
|**$tplCacheTime**|*integer*|Define the live time (in seconds) of the cache.|*1800*|
|**$tplCacheId**|Define an identificator to the template cache.|*null*|

### Examples:
```php
    // This make access to page authorized only to authenticated users.
    protected $authNeeded = true;
```

In below example, no authenticated users will be redirected to /login page.
```php
    protected $authNeeded = true;
    protected $redirectUnsigned = [
       'segments'      => ['login'],
       'query'         => [],
        'forceRewrite' => false,
        'host'         => 'secure',
    ];
```

## Methods

|Name|Type|Description|
|---|---|---|
|**__construct()**|*public*|The constructor method.|
|**_authorizationCheck()**|*protected*|Check the user permission for the called method.|
|**_forbidden()**|*protected*|Ends application with a 403 - Forbidden page error.|
|**_pageNotFound()**|*protected*|Ends with a 404 - Page not found error.|
|**_redirect()**|*protected*|Redirect the user to another URL.|
|**_template()**|*protected*|Template initialization method.|
|**_userSpecialVerifications()**|*protected*|Do all user special verifications.|

