alter table users modify email varchar(255) default null;

drop table if exists users_connections;
create table users_connections (
  user_id int unsigned not null,
  external_id int unsigned not null,
  service varchar(32) not null,
  ctime datetime not null,
  mtime datetime not null,
  token text default null,
  secret text default null,
  name varchar(255) default null,
  screenname varchar(255) default null,
  picture varchar(255) default null
);
