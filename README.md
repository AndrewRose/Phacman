This is the start of a Pacman clone written in PHP.  So far all I've done is write scrape.php to scrape the pacman local and sync databases and insert the data into a relational database structure.

* Note: most tables are populated now.

To use:

* Create a new database called phacman.
* mysql phacman < db.sql
* php -dopen_basedir= scrape.php 
* inspect the database and query at will

## EER Diagram

![EER Diagram](http://www.andrewrose.co.uk/phacman.png "EER Diagram")
