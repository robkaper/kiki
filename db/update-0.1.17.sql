alter table users_connections add id int unsigned not null auto_increment primary key first;
alter table comments add user_connection_id int unsigned not null default 0 after user_id;
update comments c, articles a set c.object_id = a.object_id where c.object_id=a.id;
