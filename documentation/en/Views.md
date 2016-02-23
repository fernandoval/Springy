# Views

* [About](#about)
* [Creating Views](#creating-views)
* [Using Templates](#using-templates)

## About

Views are part of [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture of the framework. They are code responsible for presenting data to end users, usually files containing HTML code and special codes parsed by a template engine.

In this framework you can use two different template engines: [Twig](http://twig.sensiolabs.org/) or [Smarty](http://www.smarty.net/).

## Creating Views

The files with templates for the views, must be placed inside the templates directory. Each template drive uses a different suffix in file name. The files for Twig driver must end with *.twig.html* and the files for Smarty driver must end with *.tpl.html*.

Sample of a template to *Twig* driver.
```html
<!DOCTYPE html>
<html>
    <head>
        <title>My Webpage</title>
    </head>
    <body>
        <ul id="navigation">
        {% for item in navigation %}
            <li><a href="{{ item.href }}">{{ item.caption }}</a></li>
        {% endfor %}
        </ul>

        <h1>My Webpage</h1>
        {{ a_variable }}
    </body>
</html>
```

## Using Templates

Once you have created your template file, you will need a [controller](/documentation/en/Controllers.md) to print its content. All you need is create a FW\\[Template](/documentation/en/library/Template.md) object and use its methods.

Sample how to use a template view:
```php
    $tpl = new Template('my-template');
    $tpl->assign('var', 'a value to the variable');
    $tpl->display();
```

If your controller extends FW\\[Controller](/documentation/en/library/Controller.md) class, you can use the protected method `_template()` to create the template object, like this:
```php
    $tpl = $this->_template('my-template');
    $tpl->assign('var', 'a value to the variable');
    $tpl->display();
```
