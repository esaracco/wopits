--
-- Create wopits MySQL database and user.
--

DROP DATABASE IF EXISTS wopits;
CREATE DATABASE wopits CHARACTER SET UTF8 COLLATE utf8_bin;

GRANT ALL PRIVILEGES ON wopits.*
  TO 'wopits'@'localhost' IDENTIFIED BY '!!tobechanged!!';
