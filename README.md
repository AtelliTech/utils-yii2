# Utils for Yii2
The utilities for Yii2

## Getting Start
### Requirements
- php8.0+

### Install
```
$ /lib/path/composer require atellitech/utils-yii2
```

## Model Generator
This generator is used to create model class by particular table name.

### Getting Start
#### Add controllerMap into config file of console.
```php=
...
"controllerMap": [
    'genmodel' => [
        'class' => 'AtelliTech\Yii2\Utils\ModelGeneratorController',
        'db' => 'db' // db comopnent id default: db
        'path' => '@app/models', // store path of model class file default: @app/models
        'namespace' => 'app\models', // namespace of model class default: app\models
    ],
]
```

#### Usage
```
$ /path/to/yii genmodel/generate {tableName} --option=value...
```

#### Options
- db
Database component id
- path
Store path of model class file
- ns
Namespace of model class

## Module Generator
This genertor is used to create related files of API module by name that will create files into @app/modules/{name}.

### Getting Start
#### Add controllerMap into config file of console.
```php=
...
"controllerMap": [
    'genapi' => [
        'class' => 'AtelliTech\Yii2\Utils\ApiGeneratorController',
        'db' => 'db' // db comopnent id default: db
    ],
]
```

#### Usage
```
$ /path/to/yii genapi/generate-module {moduleName}
```

## API Generator
This genertor is used to create an API controller file into specific module name by particular table name.

### Getting Start
#### Add controllerMap into config file of console.
```php=
...
"controllerMap": [
    'genapi' => [
        'class' => 'AtelliTech\Yii2\Utils\ApiGeneratorController',
        'db' => 'db' // db comopnent id default: db
    ],
]
```

#### Usage
```
$ /path/to/yii genapi/generate {tableName} --option=value
```

#### Options
- name
Name of module

## Error Trait
This trait could attach to each class and provides store error message or detail messages

### Usage
```php=
class Abc
{
    // attach trait
    use AtelliTech\Yii2\Utils\ErrorTrait;
}

$abc = new Abc;
$abc->getErrorCode(); // get error code
```

### Methods & Properties
@see [https://github.com/AtelliTech/utils-yii2/tree/main/docs](https://github.com/AtelliTech/utils-yii2/tree/main/docs)