-- The permission rows the application asks for and does not have.
--
-- GxActiveRecord::checkPermission() looks the URL up in a PHP array, which is
-- case-sensitive, while the row that would grant it is spelled differently:
-- "CreditNote/admin" against the stored "creditNote/admin". Seventeen URLs
-- across the controllers have no row under the spelling they ask for, so those
-- pages answer 403 to every role, Admin included. That is a live bug - see
-- docs/live-bugs-found.md - and the port reproduces it exactly.
--
-- Reproducing a 403 is not the same as verifying a page. While these rows are
-- missing, the UI suite can only report "both stacks answered 403; nothing
-- compared" for about thirty cases. This fixture adds the rows the application
-- asks for and grants them to role 1, which the crawl signs in as, so those
-- pages render and are compared for real. It changes neither stack's code, and
-- perm_teardown.sql removes every row it adds.
--
-- Ids start at 9990100 so the teardown can delete by range and touch nothing
-- else. create_user_id is 1, copying the shape of the rows
-- already in the table. updated_by is NULL rather than 0: the existing rows
-- predate fk_permission_updated_by and a new 0 violates it.
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990100, 'PORT TEST CreditNote/admin', 'CreditNote/admin', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990100, 1, 9990100, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990101, 'PORT TEST CreditNote/create', 'CreditNote/create', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990101, 1, 9990101, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990102, 'PORT TEST CreditNote/delete', 'CreditNote/delete', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990102, 1, 9990102, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990103, 'PORT TEST CreditNote/update', 'CreditNote/update', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990103, 1, 9990103, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990104, 'PORT TEST CreditNote/view', 'CreditNote/view', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990104, 1, 9990104, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990105, 'PORT TEST ItemCompany/create', 'ItemCompany/create', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990105, 1, 9990105, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990106, 'PORT TEST ItemCompany/update', 'ItemCompany/update', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990106, 1, 9990106, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990107, 'PORT TEST ItemCompany/view', 'ItemCompany/view', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990107, 1, 9990107, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990108, 'PORT TEST ItemDetail/create', 'ItemDetail/create', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990108, 1, 9990108, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990109, 'PORT TEST ItemDetail/update', 'ItemDetail/update', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990109, 1, 9990109, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990110, 'PORT TEST ItemDetail/view', 'ItemDetail/view', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990110, 1, 9990110, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990111, 'PORT TEST advancePayment/delete', 'advancePayment/delete', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990111, 1, 9990111, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990112, 'PORT TEST item/extra', 'item/extra', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990112, 1, 9990112, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990113, 'PORT TEST mrn/admin', 'mrn/admin', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990113, 1, 9990113, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990114, 'PORT TEST mrn/view', 'mrn/view', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990114, 1, 9990114, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990115, 'PORT TEST purchaseOrder/admin', 'purchaseOrder/admin', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990115, 1, 9990115, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
INSERT INTO tbl_permission (id, title, url, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990116, 'PORT TEST purchaseOrder/view', 'purchaseOrder/view', 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE url = VALUES(url);
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990116, 1, 9990116, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
SELECT CONCAT('permission fixture ready: ', COUNT(*), ' urls') AS x
  FROM tbl_permission WHERE id >= 9990100;

-- One more the UI crawl could not compare, found by reading pmui's "nothing
-- compared" lines rather than its counts.
--
-- mrs/view has a permission row already (id 73) and simply is not granted to
-- any role, so this links the existing row rather than adding another. That
-- turns "both stacks answered 403" into a page the suite compares for real.
--
-- user/index, onlineOrder/index and onlineOrder/create were tried here too
-- and taken out again: neither action calls checkPermission at all, so their
-- 403 comes from Yii 1's accessRules() and no permission row can lift it.
-- Both stacks refuse them identically, which is correct, and the suite is
-- right to say it compared nothing.
INSERT INTO tbl_role_permission (id, role_id, permission_id, status, type_id, create_time, create_user_id, updated_by)
  VALUES (9990117, 1, 73, 0, 0, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);
