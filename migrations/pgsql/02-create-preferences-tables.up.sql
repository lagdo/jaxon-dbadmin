create table dbadmin_profiles (
    id serial primary key,
    title varchar(50) not null,
    description varchar(500) not null default '',
    user_id integer not null,
    foreign key(user_id) references dbadmin_users(id)
);

create table dbadmin_preferences (
    id serial primary key,
    category smallint not null,
    content json not null,
    last_update timestamp not null,
    profile_id integer not null,
    unique(id, category, profile_id),
    foreign key(profile_id) references dbadmin_profiles(id)
);
