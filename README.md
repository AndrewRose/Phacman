This is the start of a Pacman clone written in PHP.  So far all I've done is write scrape.php to scrape the pacman local and sync databases and insert the data into a relational database structure.

To use:
* Create a new database called phacman.
* mysql phacman < db.sql
* php -dopen_basedir= scrape.php 
* inspect the database and query at will

## EER Diagram

![EER Diagram](https://andrewrose.co.uk/phacman.png "EER Diagram")

## Example Queries

Who's packaged the most packages?
```
SELECT
  packager,
  count(1) as count
FROM
  phacman_package_repo
GROUP BY packager
ORDER BY count DESC;
```

List groups and their packages
```
SELECT
  a.name AS groupName,
  d.name AS packageName
FROM
  phacman_group AS a LEFT JOIN
  phacman_package_groups AS b ON a.id = b.groupId LEFT JOIN
  phacman_package_version AS c ON b.packageVersionId = c.id LEFT JOIN
  phacman_package AS d ON c.packageId = d.id;
```
