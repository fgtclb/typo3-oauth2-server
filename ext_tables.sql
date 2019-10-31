CREATE TABLE tx_oauth2server_client (
    identifier    varchar(32)  DEFAULT ''  NOT NULL,
    name          varchar(255) DEFAULT ''  NOT NULL,
    secret        varchar(100) DEFAULT ''  NOT NULL,
    redirect_uris text,
    description   text
);

CREATE TABLE tx_oauth2server_accesstoken (
    identifier  varchar(255) DEFAULT ''                    NOT NULL,
    revoked     datetime     DEFAULT NULL,
    client_id   varchar(32)  DEFAULT ''                    NOT NULL,
    expiry_date datetime     DEFAULT NULL,
    scopes      varchar(255) DEFAULT ''                    NOT NULL,

    PRIMARY KEY (identifier)
);
