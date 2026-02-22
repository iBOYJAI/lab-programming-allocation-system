-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add department_id column to subjects table (keeping department VARCHAR for backward compatibility)
ALTER TABLE subjects ADD COLUMN department_id INT NULL AFTER lab_id;
ALTER TABLE subjects ADD FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- Add department_id column to staff table (keeping department VARCHAR for backward compatibility)
ALTER TABLE staff ADD COLUMN department_id INT NULL AFTER department;
ALTER TABLE staff ADD FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- Add department_id column to students table (keeping department VARCHAR for backward compatibility)
ALTER TABLE students ADD COLUMN department_id INT NULL AFTER department;
ALTER TABLE students ADD FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

