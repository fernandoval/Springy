# Controllers

* [About](#about)
* [Creating Controllers](#creating-controllers)

## About

Controller is part of [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture of the framework. All actions is you application is started by a controller class. They are responsible for processing requests and generating responses.

## Creating Controllers

To create a controller all you need is put a file named ***nameofthepage*.page.php** containing a class named **NameOfThePage_Controller** inside the controllers directory.

The names of the files must be correspondent to the URL who the user is accessing and it is case sensitive, followed by the suffix *.page.php*.

If the user is accessing the page **/my-first-page** then the controller file must have the name **my-first-page.page.php** to the framework call it.

Your class can extends the FW\[Controller](/documentation/en/library/Controller.md) class.

The follow example show a simple controller code:

```php
use FW\Controller;

class MyController_Controller extends Controller
{
    public function _default()
    {
        $tpl = $this->_template();
        $tpl->display();
    }
}
```
