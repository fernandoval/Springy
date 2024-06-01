# Controller

*   [About](#about)
*   [Properties](#properties)
*   [Methods](#methods)
    *   [Details](#methods-details)

## About

Controller is a simple class you can extend to create
[controllers](/documentation/en/Controllers.md) for your application.

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
|**authorizationCheck()**|*protected*|Check the user permission for the called method.|
|**createTemplate()**|*protected*|Template initialization method.|
|**forbidden()**|*protected*|Ends application with a 403 - Forbidden page error.|
|**pageNotFound()**|*protected*|Ends with a 404 - Page not found error.|
|**redirect()**|*protected*|Redirect the user to another URL.|
|**userSpecialVerifications()**|*protected*|Do all user special verifications.|

## Methods Details

### __construct()

This method is called by PHP when the object is created. All default
verification is made by this method, before other methos been called by the
framework.

```php
    public function __construct()
```

### authorizationCheck()

This is an internal method you can use to check the user permission. By default
it calls the method **isPermitted()** of the
[Security/AclManager](/documentation/en/library/Security/AclManager.md) class
who calls the method **hasPermissionFor()** of your user model class. The the
user has no access grant to the module, the method *forbidden()* is invoked.

```
    protected function authorizationCheck()
```

### createTemplate()

This method can be used to start your controller's view template. A new
[Template](/documentation/en/library/Template.md) object is created, it's cache
is validated and then it is returned.

The **$template** parameter can be a *string* or an *array*. If not
**$template** parameter if defined, the framework will elect one correspondent
to the controller.

```
    protected function createTemplate($template = null)
```

### forbidden()

This method verify if neeed redirect user to another page (*$redirectUnsigned*
property) and if not send a error 403 - Forbidden to the user.

```
    protected function forbidden()
```

### pageNotFound()

This method only send a 404 - Page Not Found to the user.

```
    protected function pageNotFound()
```

### redirect()

This method redirect the user to the given URL. The parameter **$url** must be a
*string* or an *array* with segments that will be contatenated by
*URI::buildURL()* method.

```
    protected function redirect($url)
```

### userSpecialVerifications()

This method can be changed in child controller to extends all verification you
need to do on user account to grant access to page. Example: if you need to
check the user account is suspended.

They will return `true` if user can access the module or `false` if not.

```
    protected function userSpecialVerifications()
```