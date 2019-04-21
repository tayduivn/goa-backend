# Cart APIs based on SLIM

Design

1. Cart is specific to a user. So any cart can be uniqely identified by the user id.
2. Uses psr4 autoloading to autoload controller and libraries via composer.
3. Token based Authentication 
4. Url based versioning.

There are five apis:

1. Adding an item to the cart
2. Removing an item from the cart
3. Updating the count of an item in the cart.
4. Getting all the items from the cart.
5. Getting the user information.

The list of apis and their controllers can be found from the file : src/routes.php

## Installing

1. Run composer install
2. Point the web server root to the public directory of this repository.
3. Migrate the database - create a database, say "cart" with a user having the right priveleges and update the same in src/settings.php
4. Run the phinx db migration and seeder using commands - >> vendor/bin/phinx migrate -e development ; vendor/bin/phinx  seed:run

## Running

1. To test the api, the following postman collection can be imported and used : https://www.getpostman.com/collections/fb2d39dc3e389eb0973a. It has the auth headers and sample test data based on the above migrated database.
2. Also import the postman environment at root : postman_environment.json and edit the options according to the local setup. This has to be used with the above collection.
