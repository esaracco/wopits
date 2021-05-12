--
-- Create wopits tables for PostgreSQL.
--

DROP TYPE IF EXISTS enum_type CASCADE;
CREATE TYPE enum_type AS ENUM ('col', 'row');

DROP TYPE IF EXISTS enum_item CASCADE;
CREATE TYPE enum_item AS ENUM ('wall', 'wall-delete', 'cell', 'header', 'postit', 'group');

DROP TYPE IF EXISTS enum_displaymode CASCADE;
CREATE TYPE enum_displaymode AS ENUM ('list-mode', 'postit-mode');

DROP TYPE IF EXISTS enum_plugtype CASCADE;
CREATE TYPE enum_plugtype AS ENUM ('solid', 'dashed', 'a-dashed');

DROP TYPE IF EXISTS enum_plugpath CASCADE;
CREATE TYPE enum_plugpath AS ENUM ('straight', 'arc', 'fluid', 'magnet', 'grid');

DROP TABLE IF EXISTS users CASCADE;
CREATE TABLE users
(
  id SERIAL PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(255) NOT NULL,
  searchdata VARCHAR(1024) NOT NULL,
  creationdate INTEGER NOT NULL,
  updatedate INTEGER NOT NULL,
  lastconnectiondate INTEGER NOT NULL,
  about VARCHAR(2000),
  picture VARCHAR(2000),
  filetype VARCHAR(50),
  filesize INTEGER,
  visible SMALLINT NOT NULL DEFAULT 1,
  allow_emails SMALLINT NOT NULL DEFAULT 1,
  settings VARCHAR(2000) NOT NULL DEFAULT '{}'
);
CREATE UNIQUE INDEX "users-email-uidx" ON users (email);
CREATE UNIQUE INDEX "users-username-uidx" ON users (username);
CREATE INDEX "users-password-idx" ON users (password);
CREATE INDEX "users-searchdata-idx" ON users (searchdata);

DROP TABLE IF EXISTS users_tokens CASCADE;
CREATE TABLE users_tokens
(
  token CHAR(80) NOT NULL PRIMARY KEY,
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  creationdate INTEGER NOT NULL,
  persistent  SMALLINT NOT NULL DEFAULT 0
);
CREATE INDEX "users_tokens-persistent-idx" ON users_tokens (persistent);

DROP TABLE IF EXISTS users_inactive CASCADE;
CREATE TABLE users_inactive
(
  id SERIAL PRIMARY KEY,
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  creationdate INTEGER NOT NULL
);

DROP TABLE IF EXISTS walls CASCADE;
CREATE TABLE walls
(
  id SERIAL PRIMARY KEY,
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  width SMALLINT NOT NULL,
  creationdate INTEGER NOT NULL,
  name VARCHAR(50) NOT NULL,
  description VARCHAR(2000)
);
CREATE INDEX "walls-name-idx" ON walls (name);
CREATE INDEX "walls-creationdate-idx" ON walls (creationdate);

DROP TABLE IF EXISTS groups CASCADE;
CREATE TABLE groups
(
  id SERIAL PRIMARY KEY,
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  walls_id INTEGER REFERENCES walls(id) ON DELETE CASCADE,
  item_type SMALLINT NOT NULL, -- dedicated(1), generic(2)
  name VARCHAR(30) NOT NULL,
  description VARCHAR(30),
  userscount SMALLINT NOT NULL DEFAULT 0
);
CREATE INDEX "groups-item_type-idx" ON groups (item_type);

DROP TABLE IF EXISTS users_groups CASCADE;
CREATE TABLE users_groups
(
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  groups_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE
);
CREATE UNIQUE INDEX "users_groups-users_id:groups_id-uidx"
  ON users_groups (users_id, groups_id);

DROP TABLE IF EXISTS walls_groups CASCADE;
CREATE TABLE walls_groups
(
  walls_id INTEGER NOT NULL REFERENCES walls(id) ON DELETE CASCADE,
  groups_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
  -- ADMIN(1), RW(2), RO(3)
  access SMALLINT NOT NULL
);
CREATE INDEX "walls_groups-access-idx" ON walls_groups (access);

DROP TABLE IF EXISTS _perf_walls_users CASCADE;
CREATE TABLE _perf_walls_users
(
  groups_id INTEGER REFERENCES groups(id) ON DELETE CASCADE,
  walls_id INTEGER NOT NULL REFERENCES walls(id) ON DELETE CASCADE,
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  -- ADMIN(1), RW(2), RO(3)
  access SMALLINT NOT NULL,
  displayexternalref SMALLINT NOT NULL DEFAULT 0,
  displayheaders SMALLINT NOT NULL DEFAULT 1,
  displaymode enum_displaymode NOT NULL DEFAULT 'postit-mode',
  settings VARCHAR(2000) NOT NULL DEFAULT '{}'
);
CREATE INDEX "_perf_walls_users-access-idx" ON _perf_walls_users (access);

DROP TABLE IF EXISTS headers CASCADE;
CREATE TABLE headers
(
  id SERIAL PRIMARY KEY,
  walls_id INTEGER NOT NULL REFERENCES walls(id) ON DELETE CASCADE,
  item_type enum_type NOT NULL,
  item_order SMALLINT NOT NULL,
  height SMALLINT NOT NULL,
  width SMALLINT,
  title VARCHAR(50),
  picture VARCHAR(2000),
  filetype VARCHAR(50),
  filesize INTEGER
);
CREATE INDEX "headers-item_type:item_order-idx" ON headers (item_type, item_order);

DROP TABLE IF EXISTS cells CASCADE;
CREATE TABLE cells
(
  id SERIAL PRIMARY KEY,
  walls_id INTEGER NOT NULL REFERENCES walls(id) ON DELETE CASCADE,
  width SMALLINT NOT NULL,
  height SMALLINT NOT NULL,
  item_row SMALLINT NOT NULL,
  item_col SMALLINT NOT NULL
);
CREATE INDEX "cells-item_row-idx" ON cells (item_row);
CREATE INDEX "cells-item_col-idx" ON cells (item_col);

DROP TABLE IF EXISTS postits CASCADE;
CREATE TABLE postits
(
  id SERIAL PRIMARY KEY,
  cells_id INTEGER NOT NULL REFERENCES cells(id) ON DELETE CASCADE,
  width SMALLINT NOT NULL,
  height SMALLINT NOT NULL,
  item_top SMALLINT NOT NULL,
  item_left SMALLINT NOT NULL,
  item_order SMALLINT NOT NULL DEFAULT 0,
  creationdate INTEGER NOT NULL,
  attachmentscount SMALLINT NOT NULL DEFAULT 0,
  workerscount SMALLINT NOT NULL DEFAULT 0,
  commentscount SMALLINT NOT NULL DEFAULT 0,
  classcolor VARCHAR(25),
  title VARCHAR(50),
  content TEXT,
  tags VARCHAR(255),
  deadline INTEGER,
  timezone VARCHAR (30),
  obsolete SMALLINT NOT NULL DEFAULT 0,
  progress SMALLINT NOT NULL DEFAULT 0
);

DROP TABLE IF EXISTS postits_plugs CASCADE;
CREATE TABLE postits_plugs
(
  walls_id INTEGER NOT NULL REFERENCES walls(id) ON DELETE CASCADE,
  item_start INTEGER NOT NULL REFERENCES postits(id) ON DELETE CASCADE,
  item_end INTEGER NOT NULL REFERENCES postits(id) ON DELETE CASCADE,
  item_top SMALLINT,
  item_left SMALLINT,
  label VARCHAR(50),
  line_size SMALLINT,
  line_type enum_plugtype,
  line_color VARCHAR(9),
  line_path enum_plugpath,
  PRIMARY KEY (walls_id, item_start, item_end)
);

DROP TABLE IF EXISTS postits_attachments CASCADE;
CREATE TABLE postits_attachments
(
  id SERIAL PRIMARY KEY,
  postits_id INTEGER NOT NULL REFERENCES postits(id) ON DELETE CASCADE,
  -- Not a foreign key, just a helper
  walls_id INTEGER NOT NULL,
  -- Not a foreign key, just a helper
  users_id INTEGER,
  item_type VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  link VARCHAR(2000) NOT NULL,
  size INTEGER NOT NULL,
  creationdate INTEGER NOT NULL,
  title VARCHAR(255),
  description VARCHAR(2000)
);
CREATE INDEX "postits_attachments-creationdate:name-idx"
  ON postits_attachments (creationdate, name);

DROP TABLE IF EXISTS postits_workers CASCADE;
CREATE TABLE postits_workers
(
  id SERIAL PRIMARY KEY,
  postits_id INTEGER NOT NULL REFERENCES postits(id) ON DELETE CASCADE,
  -- Not a foreign key, just a helper
  walls_id INTEGER NOT NULL,
  -- Not a foreign key, just a helper
  users_id INTEGER
);
CREATE INDEX "postits_workers-walls_id-idx" ON postits_workers (walls_id);
CREATE INDEX "postits_workers-users_id-idx" ON postits_workers (users_id);

DROP TABLE IF EXISTS postits_comments CASCADE;
CREATE TABLE postits_comments
(
  id SERIAL PRIMARY KEY,
  postits_id INTEGER NOT NULL REFERENCES postits(id) ON DELETE CASCADE,
  -- Not a foreign key, just a helper
  walls_id INTEGER NOT NULL,
  -- Not a foreign key, just a helper
  users_id INTEGER,
  creationdate INTEGER NOT NULL,
  content VARCHAR(2000)
);
CREATE INDEX "postits_comments-creationdate-idx"
  ON postits_comments (creationdate);

DROP TABLE IF EXISTS postits_pictures CASCADE;
CREATE TABLE postits_pictures
(
  id SERIAL PRIMARY KEY,
  postits_id INTEGER NOT NULL REFERENCES postits(id) ON DELETE CASCADE,
  -- Not a foreign key, just a helper
  walls_id INTEGER NOT NULL,
  -- Not a foreign key, just a helper
  users_id INTEGER,
  item_type VARCHAR(50) NOT NULL,
  name VARCHAR(255) NOT NULL,
  link VARCHAR(2000) NOT NULL,
  size INTEGER NOT NULL,
  creationdate INTEGER NOT NULL
);
CREATE INDEX "postits_pictures-link-idx" ON postits_pictures (link);

DROP TABLE IF EXISTS postits_alerts CASCADE;
CREATE TABLE postits_alerts
(
  id SERIAL PRIMARY KEY,
  postits_id INTEGER NOT NULL REFERENCES postits(id) ON DELETE CASCADE,
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  walls_id INTEGER NOT NULL REFERENCES walls(id) ON DELETE CASCADE,
  alertshift INTEGER NOT NULL
);
CREATE UNIQUE INDEX "postits_alerts-postits_id:users_id-uidx" ON postits_alerts (postits_id, users_id);

DROP TABLE IF EXISTS edit_queue CASCADE;
CREATE TABLE edit_queue
(
  item_id INTEGER NOT NULL,
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  walls_id INTEGER NOT NULL REFERENCES walls(id) ON DELETE CASCADE,
  session_id INTEGER NOT NULL,
  item enum_item NOT NULL
);
CREATE INDEX "edit_queue-session_id-idx" ON edit_queue (session_id);
CREATE INDEX "edit_queue-item:item_id-idx" ON edit_queue (item, item_id);

DROP TABLE IF EXISTS messages_queue CASCADE;
CREATE TABLE messages_queue
(
  id SERIAL PRIMARY KEY,
  users_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  creationdate INTEGER NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL
);
  CREATE INDEX "messages_queue-creationdate-idx" ON messages_queue (creationdate);
