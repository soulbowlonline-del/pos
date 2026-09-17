-- A test administrator for the web-UI crawl.
--
-- The crawl has to be authenticated to reach anything, and the only Admin in
-- this database is the real one. This creates a separate account instead, with
-- a password chosen here, so no real credential is used or needed. It is
-- removed again by ui-fixture-teardown.sql.
--
-- Built by copying user 1's row rather than listing columns, because tbl_user
-- has a long tail of NOT NULL columns with no defaults.
DROP TEMPORARY TABLE IF EXISTS _uitmp;
CREATE TEMPORARY TABLE _uitmp AS SELECT * FROM tbl_user WHERE id = 1;

UPDATE _uitmp SET
  id        = 9990003,
  username  = 'porttestadmin',
  full_name = 'PORT TEST ADMIN',
  email     = 'porttestadmin@example.invalid',
  password  = MD5('PortCrawl!9990003'),
  role_id   = 1,
  state_id  = 1,
  emp_id     = NULL,   -- 0 would violate fk_user_emp_id; user 1 predates it
  updated_by = NULL;   -- same again for fk_user_updated_by

DELETE FROM tbl_user WHERE id = 9990003;
INSERT INTO tbl_user SELECT * FROM _uitmp;
DROP TEMPORARY TABLE _uitmp;

SELECT CONCAT('ui fixture ready: user 9990003 role=',
              (SELECT role_id FROM tbl_user WHERE id = 9990003)) AS x;
