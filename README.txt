Ormy, an object relational mapper, and Unity Test, a testing framework.

This is back-burnered, due to the project I'm working on not needing it.  Currently joins are not implemented, and the only data types implemented are strings and integers.

To use Ormy:
1. Include ormy.php.
2. Use the example in ormy_test.php to see how to create mappings.

To test Ormy using Unity Test:
1. Have Apache and MySQL running (try XAMPP).
2. Create a database called ormy_test.
3. Clone this repo into a directory under your htdocs folder (let's say it's called otest.
4. Import ormy_test.sql into the ormy_test database
5. Set your user and password in ormy_test.php
4. (Assuming you used "otest" in step 3), Go to http://localhost/otest/ormy_test.php 
