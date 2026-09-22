-- Removes everything perm_fixture.sql added, by id range.
DELETE FROM tbl_role_permission WHERE id >= 9990100;
DELETE FROM tbl_permission WHERE id >= 9990100;
