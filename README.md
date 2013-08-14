# cryptUser

cryptUser is a set of PHP class files used to create and manage user accounts.
The user objects include the ability to use openSSL to encrypt user content.

---------------------------------


# INSTALLATION

Copy the Class folder to your PHP application directory.

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


# DEVELOPING

Developing with cryptUser requires only a CryptDataSource and a CryptUser 
instance in your application. Pass the CryptDataSource instance (either JSON or 
MySQL) to your new CryptUser instance and then execute the various user 
functions within your PHP application.


## CryptDataSource

The CryptDataSource is an interface class used to specify the required 
functionality in the data source definitions. You must therefore create an 
instance of the finished class based on the interface to use a data source.
I.E. CryptJSONSource uses a JSON flat file as a data source while 
CryptMySQLSource uses a MySQL database as the data source.


## CryptUser

The CryptUser class provides the functions for user administration and access 
to the user encryption functions.

---------------------------------

# DOCUMENTATION

The source tree includes a Documentation folder with HTML documentation generated
from the source code using APIGen.

The API documentation can be viewed [here](http://bnielsen1965.github.io/cryptUser/).
