# Models

* [About](#about)
* [Creating Models](#creating-models)

## About

Model is part of [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture of the framework. They are objects representing business data, rules and logic.

## Creating Models

You can create model classes by extending FW\[Model](/documentation/en/library/Model.md) or its child classes.

The script files of your models must be located in the classes directory.

The names of the files must be correspondent to the name of the model, followed by the extension *.php*.

The follow example show a simple model code:

```php
use FW\Model;

class MyModel extends Model
{
    /// The table name
    protected $tableName = 'users';
    /// The primary key column
    protected $primaryKey = 'id';
    /// The name of the column where the insert datetime is saved
    protected $insertDateColumn = 'created_at';
    /// The name of the column to set a logic exclusion
    protected $deletedColumn = 'deleted';
    /// Columns who can be updated by application
    protected $writableColumns = ['first_name', 'last_name', 'email'];
}
```
