alter table users add name varchar(255) not null;
alter table users add email varchar(255) not null;
alter table users add unique key(email);

    