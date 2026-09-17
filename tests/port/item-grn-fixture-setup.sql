-- GRN fixture.
--
-- tbl_purchase_bill has foreign keys on outlet, vendor, purchase order and
-- organization, and there is exactly one outlet, so inventing ids is not an
-- option: the bills and their lines are cloned from real rows through a
-- temporary table and then given fixture ids. That inherits a valid value for
-- every column this test does not care about.
DELETE FROM tbl_purchase_bill_detail WHERE id IN (9990020, 9990021, 9990022);
DELETE FROM tbl_purchase_bill        WHERE id IN (9990010, 9990011, 9990012);
DELETE FROM tbl_user                 WHERE id = 9990004;
DELETE FROM tbl_emp                  WHERE id = 9990003;

INSERT INTO tbl_emp (id, code, name, outlet_id, status, designation_id, create_user_id)
VALUES (9990003, 'PT-EMP', 'Port Test Emp', 5, 1, 1, 1);

INSERT INTO tbl_user (id, full_name, username, email, password, contact_no, gender, role_id, state_id, is_active, emp_id, date_of_birth)
VALUES (9990004, 'Port GRN User', 'portgrnuser', 'portgrn@example.invalid',
        MD5('PortTest!9990004'), '999-0004', 0, 7, 1, 1, 9990003, '1990-01-01');

-- two unapproved bills, and one approved that must not appear in getGRN
DROP TEMPORARY TABLE IF EXISTS tmp_pb;
CREATE TEMPORARY TABLE tmp_pb SELECT * FROM tbl_purchase_bill WHERE id = 63668;
UPDATE tmp_pb SET id = 9990010, bill_no = 'PT-BILL-10', status = 0;
INSERT INTO tbl_purchase_bill SELECT * FROM tmp_pb;
UPDATE tmp_pb SET id = 9990011, bill_no = 'PT-BILL-11', status = 0;
INSERT INTO tbl_purchase_bill SELECT * FROM tmp_pb;
UPDATE tmp_pb SET id = 9990012, bill_no = 'PT-BILL-12', status = 1;
INSERT INTO tbl_purchase_bill SELECT * FROM tmp_pb;
DROP TEMPORARY TABLE tmp_pb;

-- lines on 9990010 only, so 9990011 exercises the bill-with-no-lines path.
-- Row 2 leaves approved_qty NULL and row 3 points item_id at a row that does
-- not exist (item_id carries no foreign key), which is the '' branch in
-- toArray(). item_detail_id does have one, so that branch is not reachable
-- from a fixture.
DROP TEMPORARY TABLE IF EXISTS tmp_pbd;
CREATE TEMPORARY TABLE tmp_pbd SELECT * FROM tbl_purchase_bill_detail
  WHERE id = (SELECT MIN(id) FROM tbl_purchase_bill_detail WHERE purchase_bill_id = 15303);
UPDATE tmp_pbd SET purchase_bill_id = 9990010;
UPDATE tmp_pbd SET id = 9990020, req_qty = 5,   approved_qty = 3;
INSERT INTO tbl_purchase_bill_detail SELECT * FROM tmp_pbd;
UPDATE tmp_pbd SET id = 9990021, req_qty = 2.5, approved_qty = NULL;
INSERT INTO tbl_purchase_bill_detail SELECT * FROM tmp_pbd;
UPDATE tmp_pbd SET id = 9990022, req_qty = 1,   approved_qty = 0, item_id = 99999999;
INSERT INTO tbl_purchase_bill_detail SELECT * FROM tmp_pbd;
DROP TEMPORARY TABLE tmp_pbd;

SELECT CONCAT('grn fixture: bills=',
  (SELECT COUNT(*) FROM tbl_purchase_bill WHERE id IN (9990010,9990011,9990012)),
  ' lines=', (SELECT COUNT(*) FROM tbl_purchase_bill_detail WHERE purchase_bill_id = 9990010),
  ' user=',  (SELECT COUNT(*) FROM tbl_user WHERE id = 9990004),
  ' unapproved_in_outlet5=', (SELECT COUNT(*) FROM tbl_purchase_bill WHERE outlet_id = 5 AND status = 0));
