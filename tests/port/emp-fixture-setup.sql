-- Synthetic login fixture. The password is generated for this test only and
-- belongs to no real person; it exists so the md5 comparison in the auth path
-- can be exercised without touching a real account.
-- password: PortTest!9990002   ->  md5 = see below
DELETE FROM tbl_user WHERE id = 9990002;
INSERT INTO tbl_user (id, full_name, username, email, password, contact_no, gender, role_id, state_id, is_active, date_of_birth)
VALUES (9990002, 'Port Test User', 'porttestuser', 'porttest@example.invalid',
        MD5('PortTest!9990002'), '999-0002', 0, 7, 1, 1, '1990-01-01');
SELECT CONCAT('fixture user 9990002 created, hash=', LEFT(password,8), '...') FROM tbl_user WHERE id=9990002;
