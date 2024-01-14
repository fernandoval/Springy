# URI

* [About](#about)
* [Properties](#properties)
* [Methods](#methods)
  * [Details](#methods-details)

## About

URI is a static class used by the framework to translate and construct URL and URI and query strings.

## Properties

The class has no properties you can access directly.

|Property|Type|Description|
|---|---|---|
|**$uri_string**|*private static*|The current URI|
|**$segments**|*private static*|An array of the URI segments|
|**$ignored_segments**|*private static*|An array of the URI segments ignored by the framework to translate de controller|
|**$get_params**|*private static*|An array of the variables received in the query string passed by HTTP GET method|
|**$segment_page**|*private static*|The index of the segment which determines the current page|
|**$class_controller**|*private static*|The name of the controller class|

## Methods

|Name|Type|Description|
|---|---|---|
|**_fetch_uri_string()**|*private static*|Get the URI string.|
|**_set_class_controller()**|*private static*|Define the name of the controller class.|
|**parseURI()**|*public static*|Translate the URI in segments and query string variables. This method is used by the framework starter to determine the controller which is be called.|
|**validateURI()**|*public static*|Validate the segments quantity for the current controller.|
|**getControllerClass()**|*public static*|Return the name of the controller class.|
|**getURIString()**|*public static*|Return the URI string.|
|**currentPage()**|*public static*|Return the content of the segment which represent the current page.|
|**relativePathPage()**|*public static*|Return a string with the relative path to the current page.|
|**currentPageURI()**|*public static*|Return a string with the path URL the current page (without the protocol).|
|**setCurrentPage()**|*public static*|Define the segment of the current page.|
|**getSegment()**|*public static*|Get any segment of the URI.|
|**getIgnoredSegment()**|*public static*|Get any ignored segment of the URI.|
|**getAllSegments()**|*public static*|Return the array of segments.|
|**getAllIgnoredSegments()**|*public static*|Return the array of ignored segments.|
|**addSegment()**|*public static*|Add a segment to the end of segments array.|
|**insertSegment()**|*public static*|Insert a segment in any position of the segments array.|
|**getParam()**|*public static*|Return the value of a query string variable.|
|**getParams()**|*public static*|Return the array of query string variables.|
|**requestMethod()**|*public static*|Return the request method string.|
|**removeParam()**|*public static*|Remove a variable from the array of query string variables.|
|**setParam()**|*public static*|Set value to a query string parameter.|
|**buildURL()**|*public static*|Return the string of an URI with the received parameters.|
|**httpHost()**|*public static*|Return the current host with protocol.|
|**_host()**|*private static*|Return an URL host with protocolo.|
|**encode_param()**|*private static*|Enconde an array of parameters into a query string.|
|**redirect()**|*public static*|Set a redirect status header and finish the application.|
|**makeSlug()**|*public static*|Generate a slug, removing the accented and special characters from a string and convert spaces into minus symbol.|
|**isAjaxRequest()**|*public static*|Return true if is an XML HTTL request.|

## Methods Details

### parseURI()
This method is invoked by the framework starter to initiate the internal properties. Do not call it in your application.
```php
    public static function parseURI()
```

### validateURI()
This method performs the prevalidation to the current controller.

It count the number of segments and validate against the uri configuration.
```php
    public static function validateURI()
```

### getControllerClass()
This method return the name of the controller class.
```php
    public static function getControllerClass()
```

**Sample:**
```php
    $controllerName = Springy\URI::getControllerClass();
```

### getURIString()
Return the current URI string.
```php
    public static function getURIString()
```

**Sample:**
```php
    $uri = Springy\URI::getURIString();
```

### currentPage()
Return the content of the segment which represent the current page.
```php
    public static function currentPage()
```

**Sample:**
```php
    $page = Springy\URI::currentPage();
```

### relativePathPage()
Return the content of the segment which represent the current page.
```php
    public static function relativePathPage($consider_controller_root = false)
```

**Sample:**
```php
    $path = Springy\URI::relativePathPage();
```

### currentPageURI()
Return a string with the path URL the current page (without the protocol).
```php
    public static function currentPageURI()
```

**Sample:**
```php
    $uri = Springy\URI::currentPageURI();
```

### setCurrentPage()
Define the segment of the current page.
```php
    public static function setCurrentPage($segment_num)
```
The `$segment_num` parameter is an integer with the number of the segment to fix as current page.

Returns *true* if exists a `$segment_num` relative to the current page in the array of segments or *false* if does not exists.

**Sample:**
```php
    $wasSet = Springy\URI::setCurrentPage(1);
```

### getSegment()
Get any segment of the URI.
```php
    public static function getSegment($segment_num, $relative_to_page = true, $consider_controller_root = false)
```
The `$segment_num` parameter is an integer with the number of the segment desired.

The `$relative_to_page` parameter is a boolean value to determine if the desired segment is relative to the current page (default) or the begin of the array of segments.

The `$consider_controller_root` parameter is a boolean value to determine if the number of segments of the root path of contollers must be decremented (true) or not (false = default).

Returns the value of the segment or *false* if it does not exists.

**Sample:**
```php
    $segment = Springy\URI::getSegment(0);
```

### getIgnoredSegment()
Get any ignored segment of the URI.
```php
    public static function getIgnoredSegment($segment_num)
```
The `$segment_num` parameter is an integer with the number of the segment desired.

Returns the value of the segment or *false* if it does not exists.

**Sample:**
```php
    $segment = Springy\URI::getIgnoredSegment(0);
```

### getAllSegments()
Return the array of segments.
```php
    public static function getAllSegments()
```

**Sample:**
```php
    $segments = Springy\URI::getAllSegments();
```

### getAllIgnoredSegments()
Return the array of ignored segments.
```php
    public static function getAllIgnoredSegments()
```

**Sample:**
```php
    $ignoredSegments = Springy\URI::getAllIgnoredSegments();
```

### addSegment()
Add a segment to the end of segments array.
```php
    public static function addSegment($segment)
```
The `$segment` parameter is an string with segment to be added to the end of the array of segments.

**Sample:**
```php
    Springy\URI::addSegment('test');
```

### insertSegment()
Insert a segment in any position of the segments array.
```php
    public static function insertSegment($position, $segment)
```
The `$position` parameter is an integer with the position where the segment must be inserted.

The `$segment` parameter is an string with segment to be inserted.

**Sample:**
```php
    Springy\URI::insertSegment(0, 'test');
```

**Sample:**
```php
    $var = Springy\URI::getParam('test');
```

### getParam()
Return the value of a query string variable.

The `$var` parameter is the name of the query string variable desired.

```php
    public static function getParam($var)
```
The `$var` parameter is the name of the query string variable desired.

Returns the value of the variable or *false* if it does not exists.

**Sample:**
```php
    $var = Springy\URI::getParam('test');
```

### getParams()
Return the array of query string variables.
```php
    public static function getParams()
```

Returns the array with all query string variables.

**Sample:**
```php
    $queryString = Springy\URI::getParams();
```

### requestMethod()
Return the request method string.
```php
    public static function requestMethod()
```

**Sample:**
```php
    $requestMethod = Springy\URI::requestMethod();
```

### removeParam()
Remove a variable from the array of query string variables.
```php
    public static function removeParam($var)
```
The `$var` parameter is the name of the query string variable to be deleted.

**Sample:**
```php
    Springy\URI::removeParam('test');
```

### setParam()
Set value to a query string parameter.
```php
    public static function setParam($var, $value)
```
The `$var` parameter is the name of the query string variable.

The `$value` parameter is the value to be assigned to the variable.

**Sample:**
```php
    Springy\URI::setParam('test', 'my value');
```

### buildURL()
Return the string of an URI with the received parameters.
```php
    public static function buildURL($segments = [], $query = [], $forceRewriteforceRewrite = false, $host = 'dynamic', $include_ignores_segments = true)
```
The `$segments` parameter is an array with the segments of the URL.

The `$query` parameter is an array with the query string variables.

The `$forceRewrite` parameter is a boolean value to define if URI will be writed in URL redirection form (user frendly - SEF) forced or the value of configuration will be used to it.

The `$host` parameter is an string with the host name configuration (default = 'dynamic').

The `$include_ignores_segments` parameter is a boolean value to define if URI will receive the ignored segments as prefix (default = true).

**Sample:**
```php
    $uri = Springy\URI::buildURL(['search-page'], ['q' => 'query']);
```

### httpHost()
Return the current host with protocol.
```php
    public static function httpHost()
```

**Sample:**
```php
    $host = Springy\URI::httpHost();
```

### redirect()
Set a redirect status header and finish the application.

This method sends the status header with a URI redirection to the user browser and finish the application execution.
```php
    public static function redirect($url, $header = 302)
```
The `$url` parameter is a string with the URI.

The `$header` parameter is an integer value with the redirection code (302 = permanente is the default, 301 = temporary).

**Sample:**
```php
    Springy\URI::redirect('https://www.google.com', 301);
```

### makeSlug()
Generate a slug, removing the accented and special characters from a string and convert spaces into minus symbol.
```php
    public static function makeSlug($txt, $space = '-', $accept = '', $lowercase = true)
```
The `$txt` parameter is a string with the text to be converted to slug format.

The `$space` parameter is a string with the character used as word separator. (default = '-')

The `$accept` parameter is a string with other characters to be added to regular expression of accpted characters is slug.

The `$lowercase` parameter is a boolean value that determine if the slug will be returned as lowercase string or as is.

Return the slug string.

**Sample:**
```php
    $slug = Springy\URI::makeSlug('This is a slug');

    // The result is: 'this-is-a-slug'
```

### isAjaxRequest()
Return true if is an XML HTTL request.

Test to see if a request contains the HTTP_X_REQUESTED_WITH header.
```php
    public static function isAjaxRequest()
```

Return *true* if the request is an AJAX call.

**Sample:**
```php
    $ajax = Springy\URI::isAjaxRequest();
```
