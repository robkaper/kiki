alter table sections modify type varchar(32) not null;
alter table publications add key(object_id);
alter table connections add key(external_id);
alter table connections add key(user_id);
alter table menu_items add key level_context_sortorder(level,context,sortorder);
alter table sections add key type_title(type, title);
