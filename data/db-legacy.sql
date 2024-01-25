# Legacy tables no longer supported.

drop table if exists config;
create table config (
  id bigint unsigned not null auto_increment,
  primary key(id),
  `key` varchar(255) default null,
  unique key(`key`),
  value varchar(255) default null
) default charset=utf8;

insert into config (`key`, value) values( 'dbVersion', '0.1.33' );

drop table if exists menu_items;
create table menu_items (
  id bigint unsigned not null auto_increment,
  primary key(id),
  title varchar(255) not null,
  url varchar(255) not null,
  level tinyint unsigned not null,
  context varchar(255) not null,
  admin boolean default false,
  class varchar(255) default null,
  icon varchar(255) default null,
  sortorder tinyint unsigned not null,
  key level_context_sortorder(level,context,sortorder)
) default charset=utf8;
