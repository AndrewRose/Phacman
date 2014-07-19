This is the start of a Pacman clone written in PHP.  So far all I've done is write scrape.php to scrape the pacman local and sync databases and insert the data into a relational database structure.

* Note: not all tables are populated, the next step is to import the deps, conflicts, files etc which will be an hours work as soon as I get a free hour.

To use:

1, Create a new database called phacman.
2, mysql phacman < db.sql
3, php -dopen_basedir= scrape.php 
4, inspect the database and query at will
