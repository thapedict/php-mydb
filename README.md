# INTRODUCTION

MyDB is a PHP script you can use to do basic database operations.
These operations include, but not limited to, doing CRUD operations on tables.


# INSTALLATION

**Minimum requirements**
PHP 5.3 or later
MySQL database

- How to
Copy all the files in the inc folder into your working directory.
To do CRUD operations, include (or require) at least the mydb.php and mydb_table.php files

```php
	require 'inc/mydb.php';
	require 'inc/mydb_table.php';
```

You can optionally set some default connection values by defining them in the mydb constants

```php
	define( 'MyDB_HOST', 'localhost' );
	define( 'MyDB_USERNAME', 'the_cool_admin' );
	define( 'MyDB_PASSWORD', 'Z3cur&_p@$$W0RD' );
	define( 'MyDB_DATABASE', 'my_database' );
```


# USAGE

**Example 1: Getting table rows**

```php
	$con = new mydb();
	$table = new mydb_table( $con, 'all_cars' ); // assuming 'all_cars' is your table name
	$cars = $table->get();    // returns an associative array
```
	
**Example 2: Adding a table row**

```php
	$con = new mydb();    // assuming you defined those constants
	$table = new mydb_table( $con, 'all_cars' );
	$table->add( array( 'manufacturer' => 'toyota', 'model' => 'Corolla' => 'Year' => 2017 .... ) );
```
	
**Example 3: Updating a table row**

```php
	$con = new mydb();
	$table = new mydb_table( $con, 'all_cars' );
	$table->update( array( 'id' => 100021', 'model' => 'Yaris', 'Year' => 2016 ... ) ); // Method 1
	$table->where( array( 'id' => 100021' ) )->update( array( 'model' => 'Yaris', 'Year' => 2016 ... ) ); // Method 2
```
	
	Method 1 & Method 2 do the same thing. 
	Please note: if table has primary key(s), they are required
	
**Example 4: Deleting a table row**

```php
	$con = new mydb();
	$table = new mydb_table( $con, 'all_cars' );
	$table->delete( array( 'id' => 100021' ) ); // Method 1
	$table->where( array( 'id' => 100021' ) )->delete(); // Method 2
```
	
	Method 1 & Method 2 do the same thing. 
	Please note: if table has primary key(s), they are required
	
**Note:**
All CRUD operations except Read ( table->get() ) will return boolean values based on affected rows.
table->get() will return an empty array if no rows found 
	


# CREDITS

- Authors
Thapelo Moeti (https://thapedict.co.za/)


# LICENSE

The MIT License (MIT)

Copyright (c) - 2017 - Thapelo Moeti

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

