# PHP MVC App Development Framework

Sunhill Framework is a simple, fast, and powerful PHP App Development Framework that enables you to develop more modern applications by using MVC (Model - View - Controller) pattern.

<hr>

### Table of Contents

- **[Installation](#installation)**
- **[Main Structure](#main-structure)**
- **[Directories](#directories)**
- **[Configuration](#configuration)**
- **[URL Scheme Examples](#url-scheme-examples)**
- **[Controllers](#controllers)**
- **[Views](#views)**
- **[Models](#models)**
- **[Page Caching](#page-caching)**

### Installation

1. Download all files and directories (except Examples directory).
2. Open `System/Config.php` and edit your database, cache, and system settings.
3. Create your controllers, views and models.

See below for more details.

### Main Structure

```
root
├── App
│   ├── Controllers
│   │   ├── Error.php
│   │   └── Home.php
│   ├── Models
│   │   ├── Error.php
│   │   └── Home.php
│   └── Views
│       ├── Error.php
│       └── Home.php
├── Core
│   ├── App.php
│   ├── Controller.php
│   ├── Model.php
│   └── View.php
├── Public
│   ├── cache
│   ├── css
│   ├── img
│   └── js
├── System
│   ├── Config.php
│   ├── SunCache.php
│   ├── SunDB.php
│   └── SunSitemap.php
├── .htaccess
├── index.php
└── init.php
```

### Directories

`App/Controller`: Create your custom controllers in this folder.

`App/Models`: Create your custom models in this folder.

`App/Views`: Create your custom views in this folder.

`Core`: This folder contains the main app, model, view, and controller files. All custom model, view, and controller files inherit from these. Don't make changes to these files if don't need really.

`Public`: Upload all your custom files (css, js, img, bootstrap, etc.) into this folder. Your custom views will use these files.

`System`: This folder contains system classes and config files. Make your changes only in the config file.

### Configuration

Open `System/Config.php` file and make your changes.

Database Settings:

```php
define ('DB_HOST', 'localhost'); // database host
define ('DB_PORT', '3306'); // database port
define ('DB_DBNAME', ''); // database name
define ('DB_USERNAME', ''); // database username
define ('DB_PASSWORD', ''); // database password
```

Cache Settings:

```php
$cacheConfig = [
    'cacheDir'      => '/../Public/cache', // cache folder path
    'fileExtension' => 'html', // cache file extension
    'storageTime'   => 24*60*60, // storage time (seconds)
    'contentMinify' => true, // content minification
    'showTime'      => true, // show page load time
    'sefUrl'        => true // website sef url status
];
```

System Settings:

```php
define ('SYS_PHPERR', true); // php errors (show or hide, true / false)
define ('SYS_SYSERR', false); // system errors (shor or hide, true / false)
define ('SYS_PGCACHE', false); // page caching (true / false)
define ('SYS_CHEXCLUDE', []); // excluded pages for page caching (array)
define ('SYS_HOMEPAGE', 'home'); // home page (index, home, main, etc.)
define ('SYS_ERRPAGE', 'error'); // error page (if requested page does not exist, redirect to this page)
```

### URL Scheme Examples

Sample URL:
```
https://www.web_address.com/[controller_name]/[method_name]/[parameters]
```

Call a page (controller):
```
https://www.sunhillint.com/user
```
This address will call the `User` controller.

Call a page (controller) with action (method):
```
https://www.sunhillint.com/user/list
```
This address will call the `User` controller and execute `list` method.

Call a page (controller) with action (method) and parameters:
```
https://www.sunhillint.com/user/update/3
```
This address will call the `User` controller and execute `update` method with `3` parameter.

### Controllers

Controllers respond to user actions (submitting forms, clicking links,  etc.). Controllers are classes that extend the Core\Controller class.

Controllers are stored in the `App/Controllers` folder. A sample Home and Error controllers are included. Controller classes need to be in the `App/Controllers` namespace. You can add subdirectories to organize your controllers, so when adding a route for these controllers you need to specify the namespace.

Sample controller file content (without database access, static page):

```php
public function show() {
    require_once ($this->view); // include view file (with $result content)
}
```

Sample controller file content (with database access, dynamic page):

```php
public function show() {
    if (!empty($this->model)) { // if this page needs database
        $result = ($this->model)->show(); // call model class' show method
    }
    require_once ($this->view); // include view file (with $result content)
}
```

Controller classes contain methods that are the actions. To create an action, add the method name in the controller and use this into the URL (route parameters).

Sample URL:
```
https://www.web_address.com/[controller_name]
```

All pages must have a controller file and `show` method must be in it.

### Views

Views are used to display information. View files go in the `App/Views` folder. No database access or anything like that should occur in a view file.

Views extend the `Core\View` class and if your view (page) needs database access, your values will be forwarded from the controller file.

Sample view file content for multiple records (inside html tags):

```php
foreach ($result as $row) {
    echo "...";
}
```

or (for one record):

```php
echo $result[0]['content'];
```

All pages must have a View file.

### Models

Models are used to get and store data in your application. They know nothing about how this data will be presented in the views. Models extend the `Core\Model` class and use PDO to access the database (including SunDB class). They're stored in the `App/Models` folder.

All pages must have a model file (even if it's empty) and `show` method must be in it.

Using SunDB PDO Class through main model:
```php
$result = $this->query('SELECT * FROM table_name'); // send query to the main model
return $result; // return the result to the controller
```

Using SunDB PDO Class directly:
```php
$result = ($this->pdo)->select('table_name')
                      ->run(); // select all records from the table
return $result; // return the result to the controller
```

Please see <a href="https://github.com/msbatal/PHP-PDO-Database-Class/blob/main/README.md" target="_blank">SunDB PDO Class</a> for detailed usage.

### Page Caching

The framework includes a special page caching system (SunCache class).

Caching system can be activate/deactivate in `System/Config.php` file:

```php
define ('SYS_PGCACHE', true); // page caching (true / false)
```

Excluded pages can be defined in `System/Config.php` file:

```php
define ('SYS_CHEXCLUDE', ['home', 'error']); // excluded pages for page caching (array)
```

Cache settings can be changed in `System/Config.php` file:

```php
$cacheConfig = [
    'cacheDir'      => '/../Public/cache', // cache folder path
    'fileExtension' => 'html', // cache file extension
    'storageTime'   => 24*60*60, // storage time (seconds)
    'contentMinify' => true, // content minification
    'showTime'      => true, // show page load time
    'sefUrl'        => true // website sef url status
];
```

Please see <a href="https://github.com/msbatal/PHP-Cache-Class/blob/main/README.md" target="_blank">SunCache Class</a> for detailed usage.
