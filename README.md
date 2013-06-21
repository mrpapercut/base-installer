# base installer

A script to provide the bare minimum to start building a PHP/MooTools powered website. Used for small projects, where it's feasible to get cracking right away. Of course you could copy/paste files from one project to the other every time, but now you don't have to worry about leftover data polluting your files.

Requires PHP5.x and directory read/write permissions to use.

Please note: only tested on Windows. Not likely to work on Linux/Mac (as of now)

## Description

This installer is meant for small projects, where you want a basic setup with convenient functions. Launch the script, test if it works and start coding with a working environment in under a minute.

It features the following functionalities:
* A basic mysql connection with INSERT, UPDATE, DELETE and plain query functions.
* A primitive PHP templating system
* Ajax calls in Javascript and handling in PHP
* html5doctor.com's CSS reset stylesheet
* MooTools 1.4.5 (non-compressed)
* A clear and clean folder structure

## Usage

The installer uses ```$_SERVER['DOCUMENT_ROOT']``` as its base for installations.

1. Run the installer from your browser.
2. Enter the location where you want to install to. Note: if you choose an existing folder, it will overwrite conflicting files.
3. Optional: enter your database details. This will only be saved for the current process.
4. Overview page. Click 'Install' to start.
5. Click 'Go!' to visit your newly created project.
    * Test the Ajax call function
    * Test the database connection, if you filled in the correct details at step 3
    * 'Final preparations' removes all the test-data and presents you with a clean project

## Files

The following filetree is created:
* classes
    * Ajax.class.php
    * Db.class.php
    * Site.class.php
    * Template.class.php
* css
    * reset.css
    * styles.css
* inc
    * definitions.inc.php
* js
    * Site.js
    * mootools-core-1.4.5-full-nocompat-yc.js
* templates
    * content-default.php
    * footer.php
    * header.php
* index.php

### index.php

Calls Site.class.php and initiates page

### classes/Ajax.class.php

The heart of all interaction between JS and PHP, this file handles all Ajax requests.

#### function getRequest()

```php
function getRequest($request [, $data])
```
Returns __called function__

Looks for the request literally as a function, else use switch-statement

#### function getDefault()

```php
function getDefault([ $data ])
```
Returns __content-default.php template__

### classes/Db.class.php

Database class

#### function query()

```php
function query([ $query ])
```
Returns __error__ or __empty set__ or __results__

#### function insert()

```php
function insert($table, $data = array())
```
Returns __error__ or __true__

___$table___

Table name

___$data___

Data to be inserted

```php
Array(
    'column_name1' => 'value1',
    'column_name2' => 'value2'
);
```

#### function update()

```php
function update($table, $id, $data = array())
```
Returns __error__ or __true__

___$table___

Table name

___$id___

Row where 'id'

___$data___

Data to be inserted

```php
Array(
    'column_name1' => 'value1',
    'column_name2' => 'value2'
);
```

#### function delete()

```php
function delete($table, $id)
```
Returns __error__ or __true__

___$table___

Table name

___$id___

Row where 'id' to delete

### classes/Site.class.php

Main class

#### function __construct()

Initiates $_SESSION, loads inc/definitions.inc.php, assigns autoloader, initiates classes, processes Ajax-requests

#### function getPage()

```php
function getPage()
```

Returns __default page__

#### function error()

```php
function error($errorMsg [, $line = 'undefined', $file = 'undefined'])
```

Basic errors

Returns __error message__

___$errorMsg___

Error message to return

___$line___

Line number

___$file___

File

### css/reset.css

Html5doctor.com reset stylesheet

### css/styles.css

Empty stylesheet file

### inc/definitions.inc.php

Set Database constants. If provided in the installation, this should be filled in.

### js/mootools-core-1.4.5-full-nocompat-yc

MooTools core version 1.4.5, non-compressed

### js/Site.js

Main MooTools javascript

#### function ajaxRequest()

```js
ajaxRequest: function(request, data, success)
```

Returns __function call return__ or __console.log(error)__

Processes Ajax-requests

___request___

Function to call

___data___

JSON object with data to transfer

___success___

Callback function on success

##### Example:
```js
$('button').addEvent('click', function(event) {
    this.ajaxRequest('getDefault', {
        key1: 'value1',
        key2: {
            key2a: 'value2a',
            key2b: 'value2b'
        }
    },
    function(response) {
        console.log(response);
    })
});
```

### templates/content-default.php

Example empty template file, is called on initial loading of page

### templates/footer.php

Closes HTML tags

### templates/header.php

Calls HTML header with HTML5 doctype, stylesheets and javascript files

[![MPlogo](http://www.mrpapercut.com/images/touch-icon.png)](http://www.mrpapercut.com)