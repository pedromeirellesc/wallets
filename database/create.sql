CREATE TABLE users
(
    id         int primary key auto_increment,
    name       varchar(255) not null,
    email      varchar(255) not null unique,
    password   varchar(255) not null,
    type       ENUM('COMMON', 'SHOPKEEPER') NOT NULL,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp
) ENGINE=InnoDB;

CREATE TABLE wallets
(
    id         char(36) not null unique,
    user_id    int      not null,
    balance    bigint   not null default 0,
    created_at timestamp         default current_timestamp,
    updated_at timestamp         default current_timestamp on update current_timestamp,
    FOREIGN KEY (user_id) REFERENCES users (id),
    INDEX      idx_wallets_user_id (user_id)
) ENGINE=InnoDB;

CREATE TABLE transactions
(
    id              char(36)     not null unique,
    type            ENUM('DEPOSIT', 'WITHDRAW', 'TRANSFER') NOT NULL,
    from_wallet_id  CHAR(36) NULL,
    to_wallet_id    CHAR(36) NULL,
    amount          BIGINT NOT NULL,
    description     VARCHAR(255) NOT NULL,
    status          ENUM('PENDING', 'COMPLETED', 'FAILED') NOT NULL DEFAULT 'PENDING',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (from_wallet_id) REFERENCES wallets (id),
    FOREIGN KEY (to_wallet_id) REFERENCES wallets (id),
    INDEX       idx_transactions_from_wallet_id (from_wallet_id),
    INDEX       idx_transactions_to_wallet_id (to_wallet_id)
) ENGINE=InnoDB;