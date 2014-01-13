# cryptUser

cryptUser is a set of PHP class files used to create and manage user accounts.
The user objects include the ability to use openSSL to encrypt user content.

---------------------------------


# INSTALLATION

Copy the Class folder to your PHP application directory.

If your application is using a MySQL data source then you will need to create
the database and the database user.

If your application is using a JSON data source then you will need to create the
directory where the JSON files are stored and make sure your application has
read and write access.

---------------------------------


# DEVELOPING

Developing with cryptUser requires only a CryptDataSource and a CryptUser 
instance in your application. Pass the CryptDataSource instance (either JSON or 
MySQL) to your new CryptUser instance and then execute the various user 
functions within your PHP application.


## CryptDataSource

The CryptDataSource is an interface class used to specify the required 
functionality in the data source definitions. You must therefore create an 
instance of the finished class based on the interface to use a data source.

The API includes two data source implementations, CryptJSONSource and 
CryptMySQLSource. Each of these classes provide additional functionality beyond
that required by the interface. I.E. CryptJSONSource provides some custom
functions that can be used by your application to read and write your own JSON 
formatted files. And CryptMySQLSource provides functions to generate the SQL 
needed to create the users table in your database. See the API documentation for
more details.


## CryptUser

The CryptUser class provides the functions for user administration and access 
to the user encryption functions.


## Examples

The source tree includes an Examples directory where you can find some simple 
example scripts and applications that use both the JSON and MySQL data sources.

These examples will demonstrate how to use the API to create your own user
administration pages and how to implement the encryption functions in your 
own application.

---------------------------------


# DOCUMENTATION

The source tree includes a Documentation folder with HTML documentation generated
from the source code using APIGen.

The API documentation can be viewed [here](http://bnielsen1965.github.io/cryptUser/).

---------------------------------


# TESTING

The Tests folder includes phpunit testing files. Copy this folder to your PHP 
application directory and run phpunit Tests/.


There are also example implementations for both a JSON or MySQL data source in
the Examples/ directory. The JSON example will require proper write permissions
on the directory to enable writing to the JSON based data file. When using the
MySQL example a database must be provided and the connection settings set
appropriately in the example index.php file.

---------------------------------


# BRANCH STRATEGY

## master

The master branch contains the stable release versions.


## full
The full branch contains the stable release plus all documentation and examples.


## develop

The develop branch is unstable under development code.



---------------------------------

