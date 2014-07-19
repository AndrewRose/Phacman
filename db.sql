create table phacman_repo (
 id int primary key auto_increment,
 name varchar(128),
 unique(name)
) engine=innodb;

insert into phacman_repo(name) values('local'), ('core'), ('extra'), ('community'), ('testing');

create table phacman_package (
 id int primary key auto_increment,
 name varchar(255),
 version varchar(128),
 unique(name, version)
) engine=innodb;

create table phacman_package_repo (
 id int primary key auto_increment,
 repoId int not null,
 packageId int not null,
 description text,
 url varchar(255),
 arch enum('x86_64', 'i686', 'any'),
 builddate date,
 packager varchar(512),
 validation enum('pgp', 'none'),
 filename varchar(255),
 csize bigint,
 isize bigint,
 md5sum char(32),
 sha256sum char(64),
 pgpsig text,
 foreign key(packageId) references phacman_package(id),
 foreign key(repoId) references phacman_repo(id)
) engine=innodb;

create table phacman_package_local (
 id int primary key auto_increment,
 repoId int not null,
 packageId int not null,
 description text,
 url varchar(255),
 arch enum('x86_64', 'i686', 'any'),
 builddate date,
 installdate date,
 packager varchar(512),
 size bigint,
 reason int,
 validation enum('pgp', 'none'),
 foreign key(packageId) references phacman_package(id),
 foreign key(repoId) references phacman_repo(id)
) engine=innodb;

create table phacman_package_files (
 id int primary key auto_increment,
 packageId int,
 file varchar(255),
 foreign key(packageId) references phacman_package(id),
 unique(packageId, file)
) engine=innodb;

create table phacman_package_backup (
 id int primary key auto_increment,
 packageId int,
 file varchar(255),
 md5sum char(32), 
 foreign key(packageId) references phacman_package(id),
 unique(packageId, file)
) engine=innodb;

create table phacman_group (
 id int primary key auto_increment,
 name varchar(255),
 unique(name)
) engine=innodb;

create table phacman_package_groups (
 id int primary key auto_increment,
 packageId int,
 groupId int,
 foreign key(packageId) references phacman_package(id),
 foreign key(groupId) references phacman_package(id),
 unique(packageId, groupId)
) engine=innodb;

create table phacman_package_depends (
 id int primary key auto_increment,
 packageId int,
 packageDependId int,
 relop enum('=', '<', '>', '<=', '>='),
 version varchar(32),
 foreign key(packageId) references phacman_package(id),
 foreign key(packageDependId) references phacman_package(id),
 unique(packageId, packageDependId)
) engine=innodb;

create table phacman_package_conflicts (
 id int primary key auto_increment,
 packageId int,
 packageConflictId int,
 relop enum('=', '<', '>', '<=', '>='),
 version varchar(32),
 foreign key(packageId) references phacman_package(id),
 foreign key(packageConflictId) references phacman_package(id),
 unique(packageId,packageConflictId)
) engine=innodb;

create table phacman_package_provides (
 id int primary key auto_increment,
 packageId int,
 providePackageId int,
 version varchar(32),
 foreign key(packageId) references phacman_package(id),
 foreign key(providePackageId) references phacman_package(id),
 unique(packageId,providePackageId)
) engine=innodb;

create table phacman_license (
 id int primary key auto_increment,
 name varchar(128),
 unique(name)
) engine=innodb;

create table phacman_package_license (
 id int primary key auto_increment,
 packageId int,
 licenseId int,
 foreign key(packageId) references phacman_package(id),
 foreign key(licenseId) references phacman_package(id),
 unique(packageId,licenseId)
) engine=innodb;

create table phacman_package_optdepends (
 id int primary key auto_increment,
 packageId int,
 optdependPackageId int,
 details varchar(255),
 foreign key(packageId) references phacman_package(id),
 foreign key(optdependPackageId) references phacman_package(id),
 unique(packageId,optdependPackageId)
) engine=innodb;

create table phacman_package_replaces (
 id int primary key auto_increment,
 packageId int,
 replacePackageId int,
 foreign key(packageId) references phacman_package(id),
 foreign key(replacePackageId) references phacman_package(id),
 unique(packageId,replacePackageId)
) engine=innodb;
