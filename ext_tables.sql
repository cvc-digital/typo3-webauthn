CREATE TABLE be_users
(
    tx_cvcwebauthn_keys INT(11) DEFAULT 0
);

CREATE TABLE tx_cvcwebauthn_keys
(
    description TEXT DEFAULT '' NOT NULL,
    content TEXT NOT NULL,
    be_user INT(11) DEFAULT '1' NOT NULL,
    public_key_credential_id TEXT NOT NULL
);
