drop database if exists phacman;
create database phacman;
use phacman;

create table phacman_repo (
 id int primary key auto_increment,
 name varchar(128),
 unique(name)
) engine=innodb;

insert into phacman_repo(name) values('local'), ('core'), ('extra'), ('community'), ('testing');

create table phacman_package (
 id int primary key auto_increment,
 name varchar(255),
 unique(name)
) engine=innodb;

create table phacman_package_version (
 id int primary key auto_increment,
 packageId int not null,
 version varchar(128),
 unique(packageId, version),
 foreign key(packageId) references phacman_package(id)
) engine=innodb;

create table phacman_package_repo (
 id int primary key auto_increment,
 repoId int not null,
 packageVersionId int not null,
 description text,
 url varchar(255),
 arch enum('x86_64', 'i686', 'any'),
 builddate bigint,
 packager varchar(512),
 validation enum('pgp', 'none', ''),
 filename varchar(255),
 csize bigint,
 isize bigint default 0,
 md5sum char(32),
 sha256sum char(64),
 pgpsig text,
 unique(packageVersionId),
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(repoId) references phacman_repo(id)
) engine=innodb;

create table phacman_package_local (
 id int primary key auto_increment,
 repoId int not null,
 packageVersionId int not null,
 description text,
 url varchar(255),
 arch enum('x86_64', 'i686', 'any'),
 builddate bigint,
 installdate bigint,
 packager varchar(512),
 size bigint,
 reason int,
 validation enum('pgp', 'none', ''),
 unique(packageVersionId),
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(repoId) references phacman_repo(id)
) engine=innodb;

create table phacman_package_files (
 id int primary key auto_increment,
 packageVersionId int,
 file varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
 foreign key(packageVersionId) references phacman_package_version(id),
 unique(packageVersionId, file)
) engine=innodb;

create table phacman_package_backup (
 id int primary key auto_increment,
 packageVersionId int,
 file varchar(255),
 md5sum char(32), 
 foreign key(packageVersionId) references phacman_package_version(id),
 unique(packageVersionId, file)
) engine=innodb;

create table phacman_group (
 id int primary key auto_increment,
 name varchar(255),
 unique(name)
) engine=innodb;

create table phacman_package_groups (
 id int primary key auto_increment,
 packageVersionId int,
 groupId int,
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(groupId) references phacman_package(id),
 unique(packageVersionId, groupId)
) engine=innodb;

create table phacman_package_depends (
 id int primary key auto_increment,
 packageVersionId int,
 packageDependId int,
 relop enum('=', '<', '>', '<=', '>=', ''),
 version varchar(32),
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(packageDependId) references phacman_package(id),
 unique(packageVersionId, packageDependId)
) engine=innodb;

create table phacman_package_conflicts (
 id int primary key auto_increment,
 packageVersionId int,
 packageConflictId int,
 relop enum('=', '<', '>', '<=', '>=', ''),
 version varchar(32),
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(packageConflictId) references phacman_package(id),
 unique(packageVersionId,packageConflictId)
) engine=innodb;

create table phacman_package_provides (
 id int primary key auto_increment,
 packageVersionId int,
 providePackageId int,
 providePackageVersionId int default null,
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(providePackageId) references phacman_package(id),
 foreign key(providePackageVersionId) references phacman_package_version(id),
 unique(packageVersionId, providePackageId, providePackageVersionId)
) engine=innodb;

create table phacman_license (
 id int primary key auto_increment,
 name varchar(128),
 unique(name)
) engine=innodb;

create table phacman_package_license (
 id int primary key auto_increment,
 packageVersionId int,
 licenseId int,
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(licenseId) references phacman_package(id),
 unique(packageVersionId,licenseId)
) engine=innodb;

create table phacman_package_optdepends (
 id int primary key auto_increment,
 packageVersionId int,
 optdependPackageId int,
 details varchar(255),
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(optdependPackageId) references phacman_package(id),
 unique(packageVersionId,optdependPackageId)
) engine=innodb;

create table phacman_package_replaces (
 id int primary key auto_increment,
 packageVersionId int,
 replacePackageId int,
 foreign key(packageVersionId) references phacman_package_version(id),
 foreign key(replacePackageId) references phacman_package(id),
 unique(packageVersionId,replacePackageId)
) engine=innodb;
