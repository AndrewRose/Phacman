This is the start of a Pacman clone written in PHP.  So far all I've done is write scrape.php to scrape the pacman local and sync databases and insert the data into a relational database structure.

To use:

* Create a new database called phacman.
* mysql phacman < db.sql
* php -dopen_basedir= scrape.php 
* inspect the database and query at will

## EER Diagram

![EER Diagram](http://www.andrewrose.co.uk/phacman2.png "EER Diagram")

## Example Queries

Who's packaged the most packages?
```
select packager, count(1) as count from phacman_package_repo group by packager order by count desc;
```

List groups and their packages
```
select a.name as groupName, d.name as packageName from phacman_group as a left join phacman_package_groups as b on a.id = b.groupId left join phacman_package_version as c on b.packageVersionId = c.id left join phacman_package as d on c.packageId = d.id;
```
