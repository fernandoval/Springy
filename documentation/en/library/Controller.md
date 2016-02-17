# Controller

* [About](#about)
* [Properties](#properties)
* [Methods](#methods)
  * [Details](#methods-details)

## About

Controller is a simple class you can extend to create [controllers](/documentation/en/Controllers.md) for your application.

## Properties

All properties are *protected* and can be changed by child classes.

|Property|Type|Description|Default Value|
|---|---|---|---|
|**$authNeeded**|*boolean*|Define if the controller is restricted to signed in users.|*false*|
|**$redirectUnsigned**|*mixed*|Define a URL to redirect the user if it is not signed ($authNeeded must be true). Can be a string or an array used by URI::buildUrl();|*false|
|**$signedUser**|*object*|The current user signed in object.|*null*|
|**$tplIsCached**|*boolean*|Define if the template's page must be cached.|*false*|
|**$tplCacheTime**|*integer*|Define the live time (in seconds) of the cache.|*1800*|
|**$tplCacheId**|*string*|Define an identificator to the template cache.|*null*|

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

## Methods Details

### __construct()
This method is called by PHP when the object is created. All default verification is made by this method, before other methos been called by the framework.
```php
    public function __construct()
```

### _authorizationCheck()
This is an internal method you can use to check the user permission. By default it calls the method **isPermitted()** of the [Security/AclManager](/documentation/en/library/Security/AclManager.md) class who calls the method **getPermissionFor()** of your user model class. The the user has no access grant to the module, the method *_forbidden()* is invoked.
```
    protected function _authorizationCheck()
```

### _forbidden()
This method verify if neeed redirect user to another page (*$redirectUnsigned* property) and if not send a error 403 - Forbidden to the user.
```
    protected function _forbidden()
```

### _pageNotFound()
This method only send a 404 - Page Not Found to the user.
```
    protected function _pageNotFound()
```

### _redirect()
This method redirect the user to the given URL. The parameter **$url** must be a *string* or an *array* with segments that will be contatenated by *URI::buildURL()* method.
```
    protected function _redirect($url)
```

### _template()
This method can be used to start your controller's view template. A new [Template](/documentation/en/library/Template.md) object is created, it's cache is validated and then it is returned.

The **$template** parameter can be a *string* or an *array*. If not **$template** parameter if defined, the framework will elect one correspondent to the controller.
```
    protected function _template($template = null)
```

### _userSpecialVerifications()
This method can be changed in child controller to extends all verification you need to do on user account to grant access to page. Example: if you need to check the user account is suspended.

They will return `true` if user can access the module or `false` if not.
```
    protected function _userSpecialVerifications()
```