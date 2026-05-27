DELIMITER //

DROP PROCEDURE IF EXISTS sp_authenticate_user //

CREATE PROCEDURE sp_authenticate_user(IN p_identifier VARCHAR(255))
BEGIN
    SELECT id, contractor_id, name, email, mobile, role, password, status, must_change_password
    FROM users
    WHERE (contractor_id = p_identifier OR email = p_identifier OR mobile = p_identifier)
    AND status = 'active'
    LIMIT 1;
END //

DELIMITER ;
