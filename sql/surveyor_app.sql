-- Create database
CREATE DATABASE IF NOT EXISTS surveyor_app;
USE surveyor_app;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'surveyor'),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Clients table
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    address TEXT,
    cnic VARCHAR(15),
    contact_no_1 VARCHAR(15),
    contact_no_2 VARCHAR(15),
    email VARCHAR(100),
    location_map TEXT,
    whatsapp VARCHAR(15),
    bill_no VARCHAR(50),
    bill_image VARCHAR(255),
    cnic_image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Assignments (Surveyor assigned to client)
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    surveyor_id INT,
    assigned_date DATE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (surveyor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Surveys
CREATE TABLE surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    system_type VARCHAR(50),
    connection_type VARCHAR(50),
    service_type VARCHAR(50),
    system_kw DECIMAL(5,2),
    inverter_details TEXT,
    panel_details TEXT,
    battery_details TEXT,
    cables_details TEXT,
    other_equipment TEXT,
    net_metering_status VARCHAR(50),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Survey Images
CREATE TABLE survey_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT,
    image_type VARCHAR(50),
    image_path VARCHAR(255),
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

-- Quotations
CREATE TABLE quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    quotation_date DATE,
    total_amount DECIMAL(10,2),
    gst_included BOOLEAN,
    details TEXT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

ALTER TABLE surveys ADD COLUMN token VARCHAR(64) UNIQUE AFTER id;

-- CR / Modifications - 29-07-2025

ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER name;

ALTER TABLE clients
ADD COLUMN contact_no_3 VARCHAR(30) NULL AFTER contact_no_2,
ADD COLUMN client_attendent VARCHAR(100) NULL AFTER contact_no_3,
ADD COLUMN attendent_contact_1 VARCHAR(30) NULL AFTER client_attendent,
ADD COLUMN attendent_contact_2 VARCHAR(30) NULL AFTER attendent_contact_1;

ALTER TABLE surveys
ADD COLUMN esa_serial VARCHAR(50) AFTER id,
ADD COLUMN bill_no VARCHAR(50) AFTER service_type,
ADD COLUMN bill_pic VARCHAR(255) AFTER bill_no,
ADD COLUMN sanction_load VARCHAR(50) AFTER bill_pic,

-- Panel section
ADD COLUMN panel_model_no VARCHAR(100),
ADD COLUMN panel_type VARCHAR(50),
ADD COLUMN panel_manufacturer VARCHAR(50),
ADD COLUMN panel_power VARCHAR(50),
ADD COLUMN panel_count INT,
ADD COLUMN panel_box_count INT,
ADD COLUMN panel_boxes TEXT,
ADD COLUMN panel_pic VARCHAR(255),

-- Inverter section
ADD COLUMN inverter_count INT,
ADD COLUMN inverter_1_kw VARCHAR(50),
ADD COLUMN inverter_1_manufacturer VARCHAR(100),
ADD COLUMN inverter_1_model VARCHAR(100),
ADD COLUMN inverter_1_id VARCHAR(50),
ADD COLUMN inverter_1_password VARCHAR(50),
ADD COLUMN inverter_1_pic VARCHAR(255),
ADD COLUMN inverter_1_panel_count INT,
ADD COLUMN inverter_1_box_count INT,
ADD COLUMN inverter_1_boxes TEXT,

ADD COLUMN inverter_2_kw VARCHAR(50),
ADD COLUMN inverter_2_manufacturer VARCHAR(100),
ADD COLUMN inverter_2_model VARCHAR(100),
ADD COLUMN inverter_2_id VARCHAR(50),
ADD COLUMN inverter_2_password VARCHAR(50),
ADD COLUMN inverter_2_pic VARCHAR(255),
ADD COLUMN inverter_2_panel_count INT,
ADD COLUMN inverter_2_box_count INT,
ADD COLUMN inverter_2_boxes TEXT,

-- Battery section
ADD COLUMN battery_installed BOOLEAN DEFAULT 0,
ADD COLUMN battery_1_name VARCHAR(100),
ADD COLUMN battery_1_model VARCHAR(100),
ADD COLUMN battery_1_type VARCHAR(100),
ADD COLUMN battery_1_serial VARCHAR(100),
ADD COLUMN battery_1_volt VARCHAR(50),
ADD COLUMN battery_1_amp VARCHAR(50),
ADD COLUMN battery_1_cell VARCHAR(50),

ADD COLUMN battery_2_name VARCHAR(100),
ADD COLUMN battery_2_model VARCHAR(100),
ADD COLUMN battery_2_type VARCHAR(100),
ADD COLUMN battery_2_serial VARCHAR(100),
ADD COLUMN battery_2_volt VARCHAR(50),
ADD COLUMN battery_2_amp VARCHAR(50),
ADD COLUMN battery_2_cell VARCHAR(50),

ADD COLUMN battery_3_name VARCHAR(100),
ADD COLUMN battery_3_model VARCHAR(100),
ADD COLUMN battery_3_type VARCHAR(100),
ADD COLUMN battery_3_serial VARCHAR(100),
ADD COLUMN battery_3_volt VARCHAR(50),
ADD COLUMN battery_3_amp VARCHAR(50),
ADD COLUMN battery_3_cell VARCHAR(50),

-- Cables (flattened version for now)
ADD COLUMN ac_cables TEXT,
ADD COLUMN dc_cables TEXT,
ADD COLUMN battery_cables TEXT,

-- Others (checkboxes)
ADD COLUMN light_arrester BOOLEAN DEFAULT 0,
ADD COLUMN smart_controller BOOLEAN DEFAULT 0,
ADD COLUMN zero_export BOOLEAN DEFAULT 0,
ADD COLUMN light_earthing BOOLEAN DEFAULT 0,
ADD COLUMN delta_hub BOOLEAN DEFAULT 0,
ADD COLUMN ac_earthing BOOLEAN DEFAULT 0,
ADD COLUMN dc_earthing BOOLEAN DEFAULT 0,

-- Net Metering Status
ADD COLUMN net_metering_progress VARCHAR(100);

ALTER TABLE surveys
ADD COLUMN user_id INT DEFAULT NULL AFTER client_id;


ALTER TABLE surveys
ADD CONSTRAINT fk_survey_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;



