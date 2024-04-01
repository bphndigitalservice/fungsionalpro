create table reg_provinces
(
    id   integer      not null
        primary key,
    name varchar(255) not null
);

create table reg_regencies
(
    id          integer      not null
        primary key,
    province_id integer      not null
        constraint fk_province
            references reg_provinces,
    name        varchar(255) not null
);


create table reg_districts
(
    id         integer      not null
        primary key,
    regency_id integer      not null
        constraint fk_regency
            references reg_regencies,
    name       varchar(255) not null
);
