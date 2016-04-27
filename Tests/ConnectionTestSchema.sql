CREATE TABLE parameter (
    id      INT NOT NULL AUTO_INCREMENT,
    name    VARCHAR(255),
    param1  VARCHAR(10),
    param2  VARCHAR(10),
    param3  VARCHAR(10),
    param4  VARCHAR(10),
    `order` INT,
    PRIMARY KEY (id)
);

CREATE TABLE person (
    id       INT NOT NULL AUTO_INCREMENT,
    creation DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE person_parameter (
    id           INT NOT NULL AUTO_INCREMENT,
    parameter_id INT NOT NULL,
    person_id    INT NOT NULL,
    `value`      VARCHAR(255),

    PRIMARY KEY (id),
    FOREIGN KEY (parameter_id) REFERENCES parameter (id),
    FOREIGN KEY (person_id) REFERENCES person (id)
);

CREATE TABLE int_table (
    id      INT NOT NULL AUTO_INCREMENT,
    `value` INT NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE blob_table (
    id      INT NOT NULL AUTO_INCREMENT,
    `value` BLOB,
    PRIMARY KEY (id)
);