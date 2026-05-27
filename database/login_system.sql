-- Authentication Procedure
DROP PROCEDURE IF EXISTS sp_authenticate_user;

CREATE PROCEDURE sp_authenticate_user(IN p_identifier VARCHAR(255))
BEGIN
    SELECT id, contractor_id, name, email, mobile, role, password, status, must_change_password
    FROM users
    WHERE (contractor_id = p_identifier OR email = p_identifier OR mobile = p_identifier)
    AND status = 'active'
    LIMIT 1;
END;

-- Login Audit Log Table
CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    identifier VARCHAR(255),
    ip_address VARCHAR(45),
    status ENUM('success', 'failed') NOT NULL,
    failure_reason VARCHAR(255),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
