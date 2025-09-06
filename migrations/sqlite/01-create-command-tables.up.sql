create table dbadmin_owners (
    id integer not null primary key autoincrement,
    username varchar(150) not null,
    unique(username)
);

create table dbadmin_commands (
    id integer not null primary key autoincrement,
    query text not null,
    category smallint not null,
    updated_at timestamp not null,
    owner_id integer not null,
    foreign key(owner_id) references dbadmin_owners(id)
);

create table dbadmin_command_options (
    id integer not null primary key autoincrement,
    title varchar(150) not null default '',
    details text not null default '{}',
    command_id integer not null,
    foreign key(command_id) references dbadmin_commands(id),
    unique(command_id)
);

create table dbadmin_tags (
    id integer not null primary key autoincrement,
    title varchar(150) not null,
    owner_id integer not null,
    foreign key(owner_id) references dbadmin_owners(id)
);
create unique index dbadmin_tags_title_owner_unique on dbadmin_tags(title, owner_id);

create table dbadmin_command_tag (
    command_id integer not null,
    tag_id integer not null,
    foreign key(command_id) references dbadmin_commands(id),
    foreign key(tag_id) references dbadmin_tags(id)
);
create unique index dbadmin_command_tag_unique on dbadmin_command_tag(command_id, tag_id);
