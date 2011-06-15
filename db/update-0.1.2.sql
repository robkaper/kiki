drop table if exists mail_queue;
create table mail_queue (
  id int unsigned not null auto_increment,
  primary key(id),
  msg_id varchar(255) not null,
  unique key(msg_id),
  ctime datetime not null,
  mtime datetime not null,
  lock_id varchar(255) default null,
  priority smallint unsigned not null default 0,
  sent boolean not null default false,
  subject text not null,
  `from` text not null,
  `to` text not null,
  headers text not null,
  body text not null
);
