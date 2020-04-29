--
-- Create wopits tables.
--

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS users;
CREATE TABLE users
(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  username VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(255) NOT NULL,
  searchdata VARCHAR(1024) NOT NULL,
  creationdate INT UNSIGNED NOT NULL,
  updatedate INT UNSIGNED NOT NULL,
  lastconnectiondate INT UNSIGNED NOT NULL,
  about VARCHAR(2000),
  picture VARCHAR(2000),
  filetype VARCHAR(50),
  filesize INT,
  settings VARCHAR(2000) NOT NULL DEFAULT '{}',

  PRIMARY KEY (id),
  UNIQUE KEY `users_email_uidx` (email),
  INDEX `users-username-idx` (username),
  INDEX `users-password-idx` (password),
  INDEX `users-email-idx` (email),
  FULLTEXT INDEX `users-searchdata-idx` (searchdata)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS users_tokens;
CREATE TABLE users_tokens
(
  token CHAR(80) NOT NULL,
  users_id INT UNSIGNED NOT NULL,
  creationdate INT UNSIGNED NOT NULL,
  expiredate INT UNSIGNED,

  PRIMARY KEY (token),
  CONSTRAINT `users_tokens-users_id-fk` FOREIGN KEY (users_id)
    REFERENCES users(id) ON DELETE CASCADE,
  INDEX `users_tokens-expiredate-idx` (expiredate)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS walls;
CREATE TABLE walls
(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  users_id INT UNSIGNED NOT NULL,
  width SMALLINT UNSIGNED NOT NULL,
  creationdate INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(2000),

  PRIMARY KEY (id),
  CONSTRAINT `walls-users_id-fk` FOREIGN KEY (users_id)
    REFERENCES users(id) ON DELETE CASCADE,
  INDEX `walls-name-idx` (name),
  INDEX `walls-creationdate-idx` (creationdate)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS groups;
CREATE TABLE groups
(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  users_id INT UNSIGNED NOT NULL,
  walls_id INT UNSIGNED,
  `type` TINYINT UNSIGNED NOT NULL, -- dedicated(1), generic(2)
  name VARCHAR(255) NOT NULL,
  description VARCHAR(2000),
  userscount TINYINT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  UNIQUE KEY (name, users_id),
  CONSTRAINT `groups-users_id-fk` FOREIGN KEY (users_id)
    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT `groups-walls_id-fk` FOREIGN KEY (walls_id)
    REFERENCES walls(id) ON DELETE CASCADE,
  INDEX `groups-type-idx` (`type`)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS users_groups;
CREATE TABLE users_groups
(
  users_id INT UNSIGNED NOT NULL,
  groups_id INT UNSIGNED NOT NULL,

  CONSTRAINT `users_groups-users_id-fk` FOREIGN KEY (users_id)
    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT `users_groups-groups_id-fk` FOREIGN KEY (groups_id)
    REFERENCES groups(id) ON DELETE CASCADE,
  UNIQUE KEY `users_groups-users_id:groups_id-uidx` (users_id, groups_id)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS walls_groups;
CREATE TABLE walls_groups
(
  walls_id INT UNSIGNED NOT NULL,
  groups_id INT UNSIGNED NOT NULL,
  access ENUM('1', '2', '3') NOT NULL, -- ADMIN(1), RW(2), RO(3)

  CONSTRAINT `walls_groups-walls_id-fk` FOREIGN KEY (walls_id)
    REFERENCES walls(id) ON DELETE CASCADE,
  CONSTRAINT `walls_groups-groups_id-fk` FOREIGN KEY (groups_id)
    REFERENCES groups(id) ON DELETE CASCADE,
  INDEX `walls_groups-access-idx` (access)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS headers;
CREATE TABLE headers
(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  walls_id INT UNSIGNED NOT NULL,
  `type` ENUM('col', 'row') NOT NULL,
  `order` TINYINT UNSIGNED NOT NULL,
  height SMALLINT UNSIGNED NOT NULL,
  width SMALLINT UNSIGNED,
  title VARCHAR(255),
  picture VARCHAR(2000),
  filetype VARCHAR(50),
  filesize INT,

  PRIMARY KEY (id),
  CONSTRAINT `headers-walls_id-fk` FOREIGN KEY (walls_id)
    REFERENCES walls(id) ON DELETE CASCADE,
  INDEX `headers-type:order-idx` (`type`, `order`)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS cells;
CREATE TABLE cells
(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  walls_id INT UNSIGNED NOT NULL,
  width SMALLINT UNSIGNED NOT NULL,
  height SMALLINT UNSIGNED NOT NULL,
  `row` TINYINT UNSIGNED NOT NULL,
  col TINYINT UNSIGNED NOT NULL,

  PRIMARY KEY (id),
  CONSTRAINT `cells-walls_id-fk` FOREIGN KEY (walls_id)
    REFERENCES walls(id) ON DELETE CASCADE,
  INDEX `cells-row-idx` (`row`),
  INDEX `cells-col-idx` (col)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS postits;
CREATE TABLE postits
(
  -- FIXME -> INT AUTO_INCREMENT
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cells_id INT UNSIGNED NOT NULL,
  width SMALLINT UNSIGNED NOT NULL,
  height SMALLINT UNSIGNED NOT NULL,
  top SMALLINT UNSIGNED NOT NULL,
  `left` SMALLINT UNSIGNED NOT NULL,
  creationdate INT UNSIGNED NOT NULL,
  attachmentscount SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  classcolor VARCHAR(25),
  title VARCHAR(255),
  content TEXT,
  tags VARCHAR(255),
  deadline INT UNSIGNED,
  timezone VARCHAR (30),
  obsolete TINYINT(1) NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  CONSTRAINT `postits-cells_id-fk` FOREIGN KEY (cells_id)
    REFERENCES cells(id) ON DELETE CASCADE
)
ENGINE=INNODB;

DROP TABLE IF EXISTS postits_plugs;
CREATE TABLE postits_plugs
(
  walls_id INT UNSIGNED NOT NULL,
  start BIGINT UNSIGNED NOT NULL,
  end BIGINT UNSIGNED NOT NULL,
  label VARCHAR(255),

  PRIMARY KEY (walls_id, start, end),
  CONSTRAINT `postits_plugs-walls_id-fk` FOREIGN KEY (walls_id)
    REFERENCES walls(id) ON DELETE CASCADE,
  CONSTRAINT `postits_plugs-start-fk` FOREIGN KEY (start)
    REFERENCES postits(id) ON DELETE CASCADE,
  CONSTRAINT `postits_plugs-end-fk` FOREIGN KEY (end)
    REFERENCES postits(id) ON DELETE CASCADE
)
ENGINE=INNODB;

DROP TABLE IF EXISTS postits_attachments;
CREATE TABLE postits_attachments
(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  -- FIXME -> INT
  postits_id BIGINT UNSIGNED NOT NULL,
  walls_id INT UNSIGNED NOT NULL COMMENT "Not a foreign key, just a helper",
  users_id INT UNSIGNED COMMENT "Not a foreign key, just a helper",
  `type` VARCHAR(50) NOT NULL,
  name VARCHAR(255) NOT NULL,
  link VARCHAR(2000) NOT NULL,
  size INT NOT NULL,
  creationdate INT UNSIGNED NOT NULL,

  PRIMARY KEY (id),
  CONSTRAINT `postits_attachments-postits_id-fk` FOREIGN KEY (postits_id)
    REFERENCES postits(id) ON DELETE CASCADE,
  INDEX `postits_attachments-creationdate:name-idx` (creationdate, name)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS postits_pictures;
CREATE TABLE postits_pictures
(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  -- FIXME -> INT
  postits_id BIGINT UNSIGNED NOT NULL,
  walls_id INT UNSIGNED NOT NULL COMMENT "Not a foreign key, just a helper",
  users_id INT UNSIGNED COMMENT "Not a foreign key, just a helper",
  `type` VARCHAR(50) NOT NULL,
  name VARCHAR(255) NOT NULL,
  link VARCHAR(2000) NOT NULL,
  size INT NOT NULL,
  creationdate INT UNSIGNED NOT NULL,

  PRIMARY KEY (id),
  CONSTRAINT `postits_pictures-postits_id-fk` FOREIGN KEY (postits_id)
    REFERENCES postits(id) ON DELETE CASCADE,
  INDEX `postits_pictures-link-idx` (link)
)
ENGINE=INNODB;

DROP TABLE IF EXISTS edit_queue;
CREATE TABLE edit_queue
(
  item_id BIGINT UNSIGNED NOT NULL,
  users_id INT UNSIGNED NOT NULL,
  walls_id INT UNSIGNED NOT NULL,
  session_id INT UNSIGNED NOT NULL,
  item ENUM('wall', 'wall-delete', 'cell', 'header', 'postit', 'group') NOT NULL,
  updatedate INT UNSIGNED NOT NULL,

  CONSTRAINT `edit_queue-users_id-fk` FOREIGN KEY (users_id)
    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT `edit_queue-walls_id-fk` FOREIGN KEY (walls_id)
    REFERENCES walls(id) ON DELETE CASCADE,
  INDEX `edit_queue-session_id-idx` (session_id),
  INDEX `edit_queue-item:item_id-idx` (item, item_id)
)
ENGINE=INNODB;
