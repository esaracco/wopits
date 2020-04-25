--
-- Create wopits MySQL database and user.
--

DROP DATABASE IF EXISTS `wopits-example`;
CREATE DATABASE `wopits-example` CHARACTER SET UTF8 COLLATE utf8_bin;

GRANT ALL PRIVILEGES ON `wopits-example`.*
  TO 'wopits-example'@'localhost' IDENTIFIED BY '!!tobechanged!!';
