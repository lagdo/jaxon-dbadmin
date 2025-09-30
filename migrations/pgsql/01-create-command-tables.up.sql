create table dbadmin_owners (
    id serial primary key,
    username varchar(150) not null,
    unique(username)
);

create table dbadmin_runned_commands (
    id serial primary key,
    query text not null,
    driver varchar(30) not null,
    options json not null,
    category smallint not null,
    last_update timestamp not null,
    owner_id integer not null,
    foreign key(owner_id) references dbadmin_owners(id)
);

create table dbadmin_stored_commands (
    id serial primary key,
    title varchar(150) not null default '',
    query text not null,
    driver varchar(30) not null,
    last_update timestamp not null,
    owner_id integer not null,
    foreign key(owner_id) references dbadmin_owners(id)
);

create table dbadmin_tags (
    id serial primary key,
    title varchar(150) not null,
    owner_id integer not null,
    unique(title, owner_id),
    foreign key(owner_id) references dbadmin_owners(id)
);

create table dbadmin_command_tag (
    command_id integer not null,
    tag_id integer not null,
    foreign key(command_id) references dbadmin_stored_commands(id),
    foreign key(tag_id) references dbadmin_tags(id),
    unique(command_id, tag_id)
);
