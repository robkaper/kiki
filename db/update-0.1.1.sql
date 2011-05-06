drop table if exists social_updates;
create table social_updates (
  id int unsigned not null auto_increment,
  primary key(id),
  ctime datetime not null,
  network varchar(255) not null,
  post text not null,
  response text not null
);
            