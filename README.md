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
        'db' => 'db', // db comopnent id default: db
        'path' => '@app/models', // store path of model class file default: @app/models
        'namespace' => 'app\models', // namespace of model class default: app\models
    ],
]
```

#### Usage
```
$ /path/to/yii genmodel {tableName} --option=value...
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
$ /path/to/yii genapi/generate-module --name={moduleName}
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
$ /path/to/yii genapi {tableName} --option=value
```

#### Options
- name
Name of module

## Container definition Dumper
Dump service and repository files to definitions of container file

### Getting Start
#### Add controllerMap into config file of console.
```php=
...
"controllerMap": [
    'container' => [
        'class' => 'AtelliTech\Yii2\Utils\ContainerController'
    ],
]
```

#### Usage
```
$ /path/to/yii container/definitions --srcPath={srcPath} --destPath={destPath} --srcNs={srcNs} --suffix={suffix}
```
