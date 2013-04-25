drop table if exists runtime;
create table runtime (
  id bigint unsigned not null auto_increment,
  primary key(id),
  `key` varchar(255) default null,
  unique key(`key`),
  value varchar(255) default null
) default charset=utf8;
