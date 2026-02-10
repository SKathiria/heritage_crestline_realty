CREATE DATABASE heritage_crestline_reality_db;
USE heritage_crestline_reality_db;

-- Customers table
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Admins table
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Property types table
CREATE TABLE property_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    is_student TINYINT(1) DEFAULT 0
);

-- Properties table
CREATE TABLE properties (
    property_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    location VARCHAR(100) NOT NULL,
    address TEXT,
    postcode VARCHAR(10),
    bedrooms INT NOT NULL,
    bathrooms INT NOT NULL,
    square_footage INT,
    type_id INT NOT NULL,
    admin_id INT NOT NULL,
    is_for_rent BOOLEAN NOT NULL,
    is_featured BOOLEAN DEFAULT 0,
    is_available BOOLEAN DEFAULT 1,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES property_types(type_id),
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- Property images table
CREATE TABLE property_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    is_primary BOOLEAN DEFAULT 0,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE
);

-- Favorites table
CREATE TABLE favorites (
    fav_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    property_id INT NOT NULL,
    favorited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    UNIQUE KEY (customer_id, property_id)
);

-- Inquiries table
CREATE TABLE inquiries (
    inquiry_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    property_id INT NOT NULL,
    admin_id INT,
    message TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'closed') DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- Bookings table
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    property_id INT NOT NULL,
    admin_id INT,
    booking_date DATETIME NOT NULL,
    end_date DATETIME,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- Contact messages table
CREATE TABLE contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied', 'archived') DEFAULT 'unread',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- Admin logs table
CREATE TABLE admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_table VARCHAR(50) NOT NULL,
    target_id INT,
    description TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- Insert admin data
INSERT INTO admins (name, username, password_hash, phone) 
VALUES 
('Admin User 1', 'admin_1', '$2y$10$8hQZ7VtTf6WJkKp5Xr3NqeYl6HdGvBb2mF9uLc1sMwRj3oNq5SvOa', '07123456789'),
('Admin User 2', 'admin_2', '$2y$10$8hQZ7VtTf6WJkKp5Xr3NqeYl6HdGvBb2mF9uLc1sMwRj3oNq5SvOa', '07987654321');

-- Insert property types
INSERT INTO property_types (type_name, is_student, description) VALUES 
('Villa', 0, 'Luxury standalone homes with private gardens'),
('Penthouse', 0, 'Top-floor luxury apartments with premium amenities'),
('Apartment', 0, 'Modern living spaces in residential buildings'),
('Bungalow', 0, 'Single-story homes ideal for comfortable living'),
('Student Accommodation', 1, 'Housing specifically designed for student needs');

-- Insert properties
INSERT INTO properties (title, description, price, location, address, postcode, bedrooms, bathrooms, square_footage, type_id, admin_id, is_for_rent, is_featured, is_available) VALUES
-- Villas (10)
('Elegant Villa in Birmingham', 'Spacious 4-bedroom villa with modern amenities and private garden.', 738647.44, 'Birmingham', '12 Park Lane', 'B1 1AA', 4, 1, 2500, 1, 1, 0, 0, 1),
('Luxury Villa in Liverpool', 'Stunning 5-bedroom villa with sea views and swimming pool.', 1477.37, 'Liverpool', '34 Riverside Drive', 'L1 0BB', 5, 2, 2800, 1, 2, 1, 1, 1),
('Contemporary Villa in Leeds', 'Modern 1-bedroom villa perfect for professionals.', 1334.31, 'Leeds', '78 High Street', 'LS1 5PQ', 1, 1, 1200, 1, 1, 1, 0, 1),
('Grand Villa in London', 'Exclusive 5-bedroom villa in prime London location.', 582005.35, 'London', '22 Kensington Gardens', 'W8 4RT', 5, 3, 3200, 1, 2, 0, 1, 1),
('Suburban Villa in Birmingham', 'Family-friendly 4-bedroom villa in quiet neighborhood.', 1014.34, 'Birmingham', '45 Oak Avenue', 'B2 2CC', 4, 2, 2200, 1, 1, 1, 1, 1),
('Hillside Villa in Leeds', 'Beautiful 4-bedroom villa with panoramic views.', 1757.75, 'Leeds', '12 Hillside Road', 'LS2 8HH', 4, 2, 2400, 1, 2, 1, 0, 1),
('Waterfront Villa in Liverpool', 'Luxury 2-bedroom villa with private dock.', 658228.60, 'Liverpool', '8 Marina Way', 'L3 7JJ', 2, 3, 1800, 1, 1, 0, 0, 1),
('Gated Villa in Manchester', 'Secure 3-bedroom villa in gated community.', 2245.80, 'Manchester', '33 Security Lane', 'M1 6KK', 3, 2, 2000, 1, 2, 1, 1, 1),
('Executive Villa in Leeds', 'Prestigious 4-bedroom villa with home office.', 674532.94, 'Leeds', '17 Executive Road', 'LS3 9LL', 4, 1, 2600, 1, 1, 0, 1, 1),
('Modern Villa in Manchester', 'Sleek 1-bedroom villa with smart home features.', 1763.60, 'Manchester', '9 Tech Park', 'M2 7MM', 1, 2, 1500, 1, 2, 1, 0, 1),

-- Penthouses (10)
('Skyline Penthouse in London', 'Luxurious penthouse with panoramic city views.', 649417.96, 'London', '100 Sky Tower', 'E1 6NN', 5, 3, 3000, 2, 1, 0, 0, 1),
('Urban Penthouse in Leeds', 'Stylish 2-bedroom penthouse in city center.', 918.43, 'Leeds', '45 City Heights', 'LS4 3OO', 2, 1, 1400, 2, 2, 1, 1, 1),
('Boutique Penthouse in Birmingham', 'Charming 1-bedroom penthouse with terrace.', 857.41, 'Birmingham', '22 Boutique Apartments', 'B3 4PP', 1, 1, 1100, 2, 1, 1, 0, 1),
('Luxury Penthouse in Birmingham', 'Spacious 3-bedroom penthouse with rooftop pool.', 2294.64, 'Birmingham', '1 Premium Plaza', 'B4 5QQ', 3, 3, 2200, 2, 2, 1, 1, 1),
('Waterfront Penthouse in Liverpool', 'Stunning penthouse with river views.', 687509.70, 'Liverpool', '7 Riverfront', 'L4 6RR', 5, 2, 2800, 2, 1, 0, 0, 1),
('Designer Penthouse in Manchester', 'Architect-designed 1-bedroom penthouse.', 1717.73, 'Manchester', '33 Design Quarter', 'M3 8SS', 1, 2, 1300, 2, 2, 1, 1, 1),
('Central Penthouse in London', 'Prime location 3-bedroom penthouse.', 1214.66, 'London', '50 Central Square', 'SW1 9TT', 3, 2, 2000, 2, 1, 1, 1, 1),
('Executive Penthouse in Manchester', 'Corporate-style 4-bedroom penthouse.', 536267.88, 'Manchester', '10 Business Park', 'M4 0UU', 4, 1, 2400, 2, 2, 0, 0, 1),
('Premium Penthouse in Liverpool', 'High-end 2-bedroom penthouse with concierge.', 709831.89, 'Liverpool', '25 Premium Tower', 'L5 1VV', 2, 2, 1700, 2, 1, 0, 1, 1),
('Cityscape Penthouse in Leeds', '3-bedroom penthouse with stunning city views.', 1868.39, 'Leeds', '18 Viewpoint', 'LS5 2WW', 3, 1, 1900, 2, 2, 1, 0, 1),

-- Apartments (10)
('Modern Apartment in London', 'Contemporary 2-bedroom apartment in vibrant area.', 1473.35, 'London', '36 Modern Mews', 'NW1 3XX', 2, 1, 900, 3, 1, 1, 0, 1),
('Spacious Apartment in Birmingham', 'Large 4-bedroom apartment perfect for families.', 2071.80, 'Birmingham', '8 Family Square', 'B5 4YY', 4, 2, 1600, 3, 2, 1, 1, 1),
('Stylish Apartment in Leeds', 'Trendy 3-bedroom apartment with balcony.', 2467.82, 'Leeds', '15 Style Court', 'LS6 5ZZ', 3, 2, 1300, 3, 1, 1, 0, 1),
('Luxury Apartment in Manchester', 'High-end 1-bedroom apartment with amenities.', 536193.42, 'Manchester', '2 Luxury Lane', 'M5 6AA', 1, 1, 800, 3, 2, 0, 1, 1),
('Central Apartment in London', 'Well-located 2-bedroom apartment near transport.', 1986.99, 'London', '7 Transit Road', 'WC1 7BB', 2, 1, 950, 3, 1, 1, 0, 1),
('Premium Apartment in Liverpool', '5-bedroom apartment with premium finishes.', 569412.87, 'Liverpool', '4 Quality Quay', 'L6 8CC', 5, 3, 2100, 3, 2, 0, 1, 1),
('Bright Apartment in Leeds', 'Sunny 3-bedroom apartment with large windows.', 1811.27, 'Leeds', '20 Sunshine Street', 'LS7 9DD', 3, 1, 1200, 3, 1, 1, 1, 1),
('Compact Apartment in Birmingham', 'Efficient 2-bedroom apartment in great location.', 1592.49, 'Birmingham', '11 Compact Living', 'B6 0EE', 2, 2, 1000, 3, 2, 1, 0, 1),
('Family Apartment in Manchester', 'Comfortable 4-bedroom apartment for families.', 2032.44, 'Manchester', '9 Home Close', 'M6 1FF', 4, 2, 1500, 3, 1, 1, 1, 1),
('Studio Apartment in Liverpool', 'Cozy studio apartment for single professionals.', 418797.51, 'Liverpool', '3 Solo Living', 'L7 2GG', 1, 1, 500, 3, 2, 0, 0, 1),

-- Bungalows (10)
('Cozy Bungalow in Leeds', 'Charming 1-bedroom bungalow with garden.', 775.65, 'Leeds', '5 Cottage Lane', 'LS8 3HH', 1, 1, 800, 4, 1, 1, 0, 1),
('Luxury Bungalow in Birmingham', 'High-end 4-bedroom bungalow with pool.', 728419.97, 'Birmingham', '1 Exclusive Drive', 'B7 4II', 4, 2, 2200, 4, 2, 0, 1, 1),
('Seaside Bungalow in Liverpool', '2-bedroom bungalow near the beach.', 861.87, 'Liverpool', '8 Coastal Road', 'L8 5JJ', 2, 1, 1000, 4, 1, 1, 0, 1),
('Spacious Bungalow in Manchester', '3-bedroom bungalow with large living areas.', 1944.89, 'Manchester', '22 Roomy Avenue', 'M7 6KK', 3, 2, 1500, 4, 2, 1, 1, 1),
('Suburban Bungalow in London', '5-bedroom bungalow in quiet suburb.', 312213.69, 'London', '17 Suburb Street', 'N1 7LL', 5, 3, 2400, 4, 1, 0, 1, 1),
('Garden Bungalow in Leeds', '3-bedroom bungalow with extensive gardens.', 1870.49, 'Leeds', '4 Green Lane', 'LS9 8MM', 3, 1, 1300, 4, 2, 1, 0, 1),
('Modern Bungalow in Liverpool', 'Contemporary 2-bedroom bungalow.', 1034.38, 'Liverpool', '6 New Design', 'L9 9NN', 2, 1, 900, 4, 1, 1, 1, 1),
('Executive Bungalow in Birmingham', '4-bedroom bungalow with home office.', 659920.85, 'Birmingham', '3 Corporate Close', 'B8 0OO', 4, 2, 2000, 4, 2, 0, 0, 1),
('Compact Bungalow in Manchester', '2-bedroom bungalow ideal for small families.', 1928.72, 'Manchester', '7 Small Gardens', 'M8 1PP', 2, 1, 950, 4, 1, 1, 1, 1),
('Central Bungalow in London', '1-bedroom bungalow in prime location.', 531416.74, 'London', '2 Central Walk', 'SW2 2QQ', 1, 1, 700, 4, 2, 0, 1, 1),

-- Student Accommodation (10)
('Student Housing - Leeds', 'Comfortable 1-bed student accommodation near university.', 981.15, 'Leeds', '10 Campus Road', 'LS10 3RR', 1, 1, 600, 5, 1, 1, 0, 1),
('Student Housing - Liverpool', '2-bed shared accommodation with study areas.', 1854.80, 'Liverpool', '25 Student Village', 'L10 4SS', 2, 1, 800, 5, 2, 1, 1, 1),
('Student Housing - Manchester', 'Affordable 1-bed student flat in city center.', 1183.71, 'Manchester', '8 College Street', 'M9 5TT', 1, 1, 550, 5, 1, 1, 1, 1),
('Student Housing - Birmingham', '3-bed student house with shared facilities.', 2250.00, 'Birmingham', '15 Uni Lane', 'B9 6UU', 3, 2, 1200, 5, 2, 1, 0, 1),
('Student Housing - London', '4-bed student accommodation near multiple universities.', 2407.25, 'London', '40 Scholar Gardens', 'WC2 8VV', 4, 2, 1500, 5, 1, 1, 1, 1),
('Student Housing - Leeds', 'Budget-friendly 1-bed student studio.', 889.60, 'Leeds', '7 Economy Place', 'LS11 9WW', 1, 1, 500, 5, 2, 1, 0, 1),
('Student Housing - Liverpool', '2-bed student flat with modern amenities.', 1025.20, 'Liverpool', '12 Graduate House', 'L11 0XX', 2, 1, 750, 5, 1, 1, 1, 1),
('Student Housing - Manchester', '3-bed student house with garden.', 1094.90, 'Manchester', '9 Study Close', 'M10 1YY', 3, 2, 1100, 5, 2, 1, 0, 1),
('Student Housing - Birmingham', '2-bed student accommodation near campus.', 999.00, 'Birmingham', '6 Academy Road', 'B10 2ZZ', 2, 1, 700, 5, 1, 1, 0, 1),
('Student Housing - London', '1-bed student studio in central location.', 1987.77, 'London', '3 Learning Lane', 'N1 8AA', 1, 1, 650, 5, 2, 1, 1, 1);

-- Villas (1-10)
-- Insert property images for ALL properties (1-50) with local file paths
INSERT INTO property_images (property_id, image_path, alt_text, is_primary) VALUES
-- Villas (1-10)
(1, 'C:\\xampp\\htdocs\\IADD\\Images\\villa1_1.jpg', 'Front view of elegant villa in Birmingham', 1),
(1, 'C:\\xampp\\htdocs\\IADD\\Images\\villa1_2.jpg', 'Living room of Birmingham villa', 0),
(1, 'C:\\xampp\\htdocs\\IADD\\Images\\villa1_3.jpg', 'Kitchen of Birmingham villa', 0),
(1, 'C:\\xampp\\htdocs\\IADD\\Images\\villa1_4.jpg', 'Garden of Birmingham villa', 0),

(2, 'C:\\xampp\\htdocs\\IADD\\Images\\villa2_1.jpg', 'Front view of luxury villa in Liverpool', 1),
(2, 'C:\\xampp\\htdocs\\IADD\\Images\\villa2_2.jpg', 'Swimming pool at Liverpool villa', 0),
(2, 'C:\\xampp\\htdocs\\IADD\\Images\\villa2_3.jpg', 'Master bedroom in Liverpool villa', 0),
(2, 'C:\\xampp\\htdocs\\IADD\\Images\\villa2_4.jpg', 'Sea view from Liverpool villa', 0),

(3, 'C:\\xampp\\htdocs\\IADD\\Images\\villa3_1.jpg', 'Contemporary villa in Leeds exterior', 1),
(3, 'C:\\xampp\\htdocs\\IADD\\Images\\villa3_2.jpg', 'Modern living space in Leeds villa', 0),
(3, 'C:\\xampp\\htdocs\\IADD\\Images\\villa3_3.jpg', 'Bedroom in Leeds villa', 0),
(3, 'C:\\xampp\\htdocs\\IADD\\Images\\villa3_4.jpg', 'Bathroom in Leeds villa', 0),

(4, 'C:\\xampp\\htdocs\\IADD\\Images\\villa4_1.jpg', 'Grand villa in London exterior', 1),
(4, 'C:\\xampp\\htdocs\\IADD\\Images\\villa4_2.jpg', 'Luxury kitchen in London villa', 0),
(4, 'C:\\xampp\\htdocs\\IADD\\Images\\villa4_3.jpg', 'Master suite in London villa', 0),
(4, 'C:\\xampp\\htdocs\\IADD\\Images\\villa4_4.jpg', 'Garden of London villa', 0),

(5, 'C:\\xampp\\htdocs\\IADD\\Images\\villa5_1.jpg', 'Suburban villa in Birmingham exterior', 1),
(5, 'C:\\xampp\\htdocs\\IADD\\Images\\villa5_2.jpg', 'Family room in Birmingham villa', 0),
(5, 'C:\\xampp\\htdocs\\IADD\\Images\\villa5_3.jpg', 'Kitchen in Birmingham villa', 0),
(5, 'C:\\xampp\\htdocs\\IADD\\Images\\villa5_4.jpg', 'Backyard of Birmingham villa', 0),

(6, 'C:\\xampp\\htdocs\\IADD\\Images\\villa6_1.jpg', 'Hillside villa in Leeds exterior', 1),
(6, 'C:\\xampp\\htdocs\\IADD\\Images\\villa6_2.jpg', 'Panoramic view from Leeds villa', 0),
(6, 'C:\\xampp\\htdocs\\IADD\\Images\\villa6_3.jpg', 'Dining area in Leeds villa', 0),
(6, 'C:\\xampp\\htdocs\\IADD\\Images\\villa6_4.jpg', 'Bedroom in Leeds villa', 0),

(7, 'C:\\xampp\\htdocs\\IADD\\Images\\villa7_1.jpg', 'Waterfront villa in Liverpool exterior', 1),
(7, 'C:\\xampp\\htdocs\\IADD\\Images\\villa7_2.jpg', 'Dock area of Liverpool villa', 0),
(7, 'C:\\xampp\\htdocs\\IADD\\Images\\villa7_3.jpg', 'Living room in Liverpool villa', 0),
(7, 'C:\\xampp\\htdocs\\IADD\\Images\\villa7_4.jpg', 'Master bathroom in Liverpool villa', 0),

(8, 'C:\\xampp\\htdocs\\IADD\\Images\\villa8_1.jpg', 'Gated villa in Manchester exterior', 1),
(8, 'C:\\xampp\\htdocs\\IADD\\Images\\villa8_2.jpg', 'Community area of Manchester villa', 0),
(8, 'C:\\xampp\\htdocs\\IADD\\Images\\villa8_3.jpg', 'Bedroom in Manchester villa', 0),
(8, 'C:\\xampp\\htdocs\\IADD\\Images\\villa8_4.jpg', 'Kitchen in Manchester villa', 0),

(9, 'C:\\xampp\\htdocs\\IADD\\Images\\villa9_1.jpg', 'Executive villa in Leeds exterior', 1),
(9, 'C:\\xampp\\htdocs\\IADD\\Images\\villa9_2.jpg', 'Home office in Leeds villa', 0),
(9, 'C:\\xampp\\htdocs\\IADD\\Images\\villa9_3.jpg', 'Living area in Leeds villa', 0),
(9, 'C:\\xampp\\htdocs\\IADD\\Images\\villa9_4.jpg', 'Garden in Leeds villa', 0),

(10, 'C:\\xampp\\htdocs\\IADD\\Images\\villa10_1.jpg', 'Modern villa in Manchester exterior', 1),
(10, 'C:\\xampp\\htdocs\\IADD\\Images\\villa10_2.jpg', 'Smart home features in Manchester villa', 0),
(10, 'C:\\xampp\\htdocs\\IADD\\Images\\villa10_3.jpg', 'Bedroom in Manchester villa', 0),
(10, 'C:\\xampp\\htdocs\\IADD\\Images\\villa10_4.jpg', 'Bathroom in Manchester villa', 0),

-- Penthouses (11-20)
(11, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse11_1.jpg', 'Skyline penthouse in London exterior', 1),
(11, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse11_2.jpg', 'City view from London penthouse', 0),
(11, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse11_3.jpg', 'Living area in London penthouse', 0),
(11, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse11_4.jpg', 'Master bedroom in London penthouse', 0),

(12, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse12_1.jpg', 'Urban penthouse in Leeds exterior', 1),
(12, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse12_2.jpg', 'City center view from Leeds penthouse', 0),
(12, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse12_3.jpg', 'Kitchen in Leeds penthouse', 0),
(12, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse12_4.jpg', 'Bedroom in Leeds penthouse', 0),

(13, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse13_1.jpg', 'Boutique penthouse in Birmingham exterior', 1),
(13, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse13_2.jpg', 'Terrace of Birmingham penthouse', 0),
(13, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse13_3.jpg', 'Living space in Birmingham penthouse', 0),
(13, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse13_4.jpg', 'Bathroom in Birmingham penthouse', 0),

(14, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse14_1.jpg', 'Luxury penthouse in Birmingham exterior', 1),
(14, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse14_2.jpg', 'Rooftop pool in Birmingham penthouse', 0),
(14, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse14_3.jpg', 'Living area in Birmingham penthouse', 0),
(14, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse14_4.jpg', 'Master suite in Birmingham penthouse', 0),

(15, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse15_1.jpg', 'Waterfront penthouse in Liverpool exterior', 1),
(15, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse15_2.jpg', 'River view from Liverpool penthouse', 0),
(15, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse15_3.jpg', 'Dining area in Liverpool penthouse', 0),
(15, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse15_4.jpg', 'Bedroom in Liverpool penthouse', 0),

(16, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse16_1.jpg', 'Designer penthouse in Manchester exterior', 1),
(16, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse16_2.jpg', 'Architectural details in Manchester penthouse', 0),
(16, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse16_3.jpg', 'Living space in Manchester penthouse', 0),
(16, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse16_4.jpg', 'Bathroom in Manchester penthouse', 0),

(17, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse17_1.jpg', 'Central penthouse in London exterior', 1),
(17, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse17_2.jpg', 'Prime location view from London penthouse', 0),
(17, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse17_3.jpg', 'Kitchen in London penthouse', 0),
(17, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse17_4.jpg', 'Bedroom in London penthouse', 0),

(18, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse18_1.jpg', 'Executive penthouse in Manchester exterior', 1),
(18, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse18_2.jpg', 'Corporate-style living area in Manchester penthouse', 0),
(18, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse18_3.jpg', 'Office space in Manchester penthouse', 0),
(18, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse18_4.jpg', 'Bedroom in Manchester penthouse', 0),

(19, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse19_1.jpg', 'Premium penthouse in Liverpool exterior', 1),
(19, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse19_2.jpg', 'Lobby area in Liverpool penthouse', 0),
(19, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse19_3.jpg', 'Living room in Liverpool penthouse', 0),
(19, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse19_4.jpg', 'Master bedroom in Liverpool penthouse', 0),

(20, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse20_1.jpg', 'Cityscape penthouse in Leeds exterior', 1),
(20, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse20_2.jpg', 'City view from Leeds penthouse', 0),
(20, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse20_3.jpg', 'Living space in Leeds penthouse', 0),
(20, 'C:\\xampp\\htdocs\\IADD\\Images\\penthouse20_4.jpg', 'Bedroom in Leeds penthouse', 0),

-- Apartments (21-30)
(21, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment21_1.jpg', 'Modern apartment in London exterior', 1),
(21, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment21_2.jpg', 'Living area in London apartment', 0),
(21, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment21_3.jpg', 'Kitchen in London apartment', 0),

(22, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment22_1.jpg', 'Spacious apartment in Birmingham exterior', 1),
(22, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment22_2.jpg', 'Family room in Birmingham apartment', 0),
(22, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment22_3.jpg', 'Kitchen in Birmingham apartment', 0),

(23, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment23_1.jpg', 'Stylish apartment in Leeds exterior', 1),
(23, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment23_2.jpg', 'Balcony view from Leeds apartment', 0),
(23, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment23_3.jpg', 'Living area in Leeds apartment', 0),

(24, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment24_1.jpg', 'Luxury apartment in Manchester exterior', 1),
(24, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment24_2.jpg', 'Amenities in Manchester apartment', 0),
(24, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment24_3.jpg', 'Living space in Manchester apartment', 0),

(25, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment25_1.jpg', 'Central apartment in London exterior', 1),
(25, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment25_2.jpg', 'Transport access near London apartment', 0),
(25, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment25_3.jpg', 'Living room in London apartment', 0),

(26, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment26_1.jpg', 'Premium apartment in Liverpool exterior', 1),
(26, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment26_2.jpg', 'High-end finishes in Liverpool apartment', 0),
(26, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment26_3.jpg', 'Living area in Liverpool apartment', 0),

(27, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment27_1.jpg', 'Bright apartment in Leeds exterior', 1),
(27, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment27_2.jpg', 'Sunlit living space in Leeds apartment', 0),
(27, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment27_3.jpg', 'Kitchen in Leeds apartment', 0),

(28, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment28_1.jpg', 'Compact apartment in Birmingham exterior', 1),
(28, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment28_2.jpg', 'Efficient layout in Birmingham apartment', 0),
(28, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment28_3.jpg', 'Kitchen in Birmingham apartment', 0),

(29, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment29_1.jpg', 'Family apartment in Manchester exterior', 1),
(29, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment29_2.jpg', 'Living space in Manchester apartment', 0),
(29, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment29_3.jpg', 'Kitchen in Manchester apartment', 0),

(30, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment30_1.jpg', 'Studio apartment in Liverpool exterior', 1),
(30, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment30_2.jpg', 'Compact living space in Liverpool studio', 0),
(30, 'C:\\xampp\\htdocs\\IADD\\Images\\apartment30_3.jpg', 'Kitchenette in Liverpool studio', 0),

-- Bungalows (31-40)
(31, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow31_1.jpg', 'Cozy bungalow in Leeds exterior', 1),
(31, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow31_2.jpg', 'Garden of Leeds bungalow', 0),
(31, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow31_3.jpg', 'Living room in Leeds bungalow', 0),

(32, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow32_1.jpg', 'Luxury bungalow in Birmingham exterior', 1),
(32, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow32_2.jpg', 'Pool area in Birmingham bungalow', 0),
(32, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow32_3.jpg', 'Living space in Birmingham bungalow', 0),

(33, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow33_1.jpg', 'Seaside bungalow in Liverpool exterior', 1),
(33, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow33_2.jpg', 'Beach access from Liverpool bungalow', 0),
(33, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow33_3.jpg', 'Living area in Liverpool bungalow', 0),

(34, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow34_1.jpg', 'Spacious bungalow in Manchester exterior', 1),
(34, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow34_2.jpg', 'Large living area in Manchester bungalow', 0),
(34, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow34_3.jpg', 'Kitchen in Manchester bungalow', 0),

(35, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow35_1.jpg', 'Suburban bungalow in London exterior', 1),
(35, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow35_2.jpg', 'Quiet street view of London bungalow', 0),
(35, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow35_3.jpg', 'Living room in London bungalow', 0),

(36, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow36_1.jpg', 'Garden bungalow in Leeds exterior', 1),
(36, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow36_2.jpg', 'Extensive gardens of Leeds bungalow', 0),
(36, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow36_3.jpg', 'Living space in Leeds bungalow', 0),

(37, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow37_1.jpg', 'Modern bungalow in Liverpool exterior', 1),
(37, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow37_2.jpg', 'Contemporary design in Liverpool bungalow', 0),
(37, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow37_3.jpg', 'Living area in Liverpool bungalow', 0),

(38, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow38_1.jpg', 'Executive bungalow in Birmingham exterior', 1),
(38, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow38_2.jpg', 'Home office in Birmingham bungalow', 0),
(38, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow38_3.jpg', 'Living room in Birmingham bungalow', 0),

(39, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow39_1.jpg', 'Compact bungalow in Manchester exterior', 1),
(39, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow39_2.jpg', 'Small garden in Manchester bungalow', 0),
(39, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow39_3.jpg', 'Living space in Manchester bungalow', 0),

(40, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow40_1.jpg', 'Central bungalow in London exterior', 1),
(40, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow40_2.jpg', 'Prime location view from London bungalow', 0),
(40, 'C:\\xampp\\htdocs\\IADD\\Images\\bungalow40_3.jpg', 'Living area in London bungalow', 0),

-- Student Accommodation (41-50)
(41, 'C:\\xampp\\htdocs\\IADD\\Images\\student41_1.jpg', 'Student housing in Leeds exterior', 1),
(41, 'C:\\xampp\\htdocs\\IADD\\Images\\student41_2.jpg', 'Study area in Leeds student housing', 0),

(42, 'C:\\xampp\\htdocs\\IADD\\Images\\student42_1.jpg', 'Student housing in Liverpool exterior', 1),
(42, 'C:\\xampp\\htdocs\\IADD\\Images\\student42_2.jpg', 'Shared living space in Liverpool student housing', 0),

(43, 'C:\\xampp\\htdocs\\IADD\\Images\\student43_1.jpg', 'Student housing in Manchester exterior', 1),
(43, 'C:\\xampp\\htdocs\\IADD\\Images\\student43_2.jpg', 'City center location of Manchester student flat', 0),

(44, 'C:\\xampp\\htdocs\\IADD\\Images\\student44_1.jpg', 'Student housing in Birmingham exterior', 1),
(44, 'C:\\xampp\\htdocs\\IADD\\Images\\student44_2.jpg', 'Shared facilities in Birmingham student house', 0),

(45, 'C:\\xampp\\htdocs\\IADD\\Images\\student45_1.jpg', 'Student housing in London exterior', 1),
(45, 'C:\\xampp\\htdocs\\IADD\\Images\\student45_2.jpg', 'University proximity of London student accommodation', 0),

(46, 'C:\\xampp\\htdocs\\IADD\\Images\\student46_1.jpg', 'Student housing in Leeds exterior', 1),
(46, 'C:\\xampp\\htdocs\\IADD\\Images\\student46_2.jpg', 'Budget-friendly studio in Leeds', 0),

(47, 'C:\\xampp\\htdocs\\IADD\\Images\\student47_1.jpg', 'Student housing in Liverpool exterior', 1),
(47, 'C:\\xampp\\htdocs\\IADD\\Images\\student47_2.jpg', 'Modern amenities in Liverpool student flat', 0),

(48, 'C:\\xampp\\htdocs\\IADD\\Images\\student48_1.jpg', 'Student housing in Manchester exterior', 1),
(48, 'C:\\xampp\\htdocs\\IADD\\Images\\student48_2.jpg', 'Garden area of Manchester student house', 0),

(49, 'C:\\xampp\\htdocs\\IADD\\Images\\student49_1.jpg', 'Student housing in Birmingham exterior', 1),
(49, 'C:\\xampp\\htdocs\\IADD\\Images\\student49_2.jpg', 'Campus proximity of Birmingham student accommodation', 0),

(50, 'C:\\xampp\\htdocs\\IADD\\Images\\student50_1.jpg', 'Student housing in London exterior', 1),
(50, 'C:\\xampp\\htdocs\\IADD\\Images\\student50_2.jpg', 'Central location of London student studio', 0);
