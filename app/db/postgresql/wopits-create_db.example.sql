--
-- Create wopits PostgreSQL database and user.
--

DROP DATABASE IF EXISTS wopits;
CREATE DATABASE wopits;

CREATE USER wopits WITH ENCRYPTED PASSWORD '!!tobechanged!!';
GRANT ALL PRIVILEGES ON DATABASE wopits TO wopits;
