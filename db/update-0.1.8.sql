drop table if exists objects;
create table objects (
  object_id bigint unsigned not null auto_increment,
  primary key(object_id),
  type varchar(32) not null,
  ctime datetime not null,
  mtime datetime not null
);

alter table articles add object_id bigint unsigned not null;
alter table articles add unique key(object_id);
