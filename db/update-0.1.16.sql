alter table sections add type varchar(255) not null;
update sections set type='articles';
drop table router_base_uris;