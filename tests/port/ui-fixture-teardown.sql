-- Removes the test administrator created by ui-fixture-setup.sql.
-- Run against both databases: the legacy 5.6 stack and the 8.3 one have
-- separate MySQL containers, and the crawl needs the account in both.
DELETE FROM tbl_user WHERE id = 9990003;
SELECT CONCAT('test admin rows remaining: ', COUNT(*)) AS x FROM tbl_user WHERE id = 9990003;
