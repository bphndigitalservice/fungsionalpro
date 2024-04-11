create table reg_regencies
(
    id          integer      not null
        primary key,
    province_id integer      not null
        constraint fk_province
            references reg_provinces,
    name        varchar(255) not null
);
