This is the start of a Pacman clone written in PHP.  So far all I've done is write scrape.php to scrape the pacman local and sync databases and insert the data into a relational database structure.

* Note: not all tables are populated, the next step is to import the deps, conflicts, files etc which will be an hours work as soon as I get a free hour.

To use:

* Create a new database called phacman.
* mysql phacman < db.sql
* php -dopen_basedir= scrape.php 
* inspect the database and query at will
