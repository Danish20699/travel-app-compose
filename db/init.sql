-- Drop tables if re-running
DROP TABLE IF EXISTS bookings CASCADE;
DROP TABLE IF EXISTS destinations CASCADE;

-- 1. Create Destinations Table
CREATE TABLE destinations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(150) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    price NUMERIC(10, 2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    rating NUMERIC(2, 1) DEFAULT 4.9,
    image_url TEXT NOT NULL,
    description TEXT NOT NULL
);

-- 2. Create Bookings Table (Interactive Booking Engine)
CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    destination_id INT REFERENCES destinations(id) ON DELETE CASCADE,
    customer_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    travel_date DATE NOT NULL,
    num_travelers INT DEFAULT 1,
    status VARCHAR(50) DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Insert Authentic Kashmir Tour Packages with Verified Photography
INSERT INTO destinations (name, location, duration, price, category, rating, image_url, description) VALUES
(
    'Gulmarg Gondola & Ski Paradise',
    'Baramulla, Kashmir',
    '4 Days / 3 Nights',
    18500.00,
    'Winter Sports',
    4.9,
    'https://images.pexels.com/photos/35672517/pexels-photo-35672517.jpeg',
    'Experience world-class skiing, ride the legendary Kongdoori & Apharwat Gondola Phase 2, and stay in cozy alpine pine luxury resorts.'
),
(
    'Dal Lake Royal Shikara & Houseboat',
    'Srinagar, Kashmir',
    '3 Days / 2 Nights',
    12500.00,
    'Lakes & Houseboats',
    4.8,
    'https://images.unsplash.com/photo-1595815771614-ade9d652a65d?auto=format&fit=crop&w=800&q=80',
    'Sleep in hand-carved heritage cedar houseboats, take sunset Shikara cruises across Char Chinar, and explore floating vegetable markets.'
),
(
    'Pahalgam & Betaab Valley Retreat',
    'Anantnag, Kashmir',
    '4 Days / 3 Nights',
    15000.00,
    'Valleys & Meadows',
    4.9,
    'https://images.pexels.com/photos/36627785/pexels-photo-36627785.jpeg',
    'Walk through evergreen pine forests of Betaab Valley, visit Aru Valley, trek Baisaran Mini-Switzerland, and experience Lidder River rafting.'
),
(
    'Sonamarg Meadow of Gold Glacier Trek',
    'Ganderbal, Kashmir',
    '3 Days / 2 Nights',
    14000.00,
    'Alpine Treks',
    4.7,
    'https://kashmirlife.net/wp-content/uploads/2020/11/Sonamarg.jpg',
    'Hike to the mighty Thajiwas Glacier, drink crystal-clear mountain water from the Sindh River, and camp under starry Himalayan skies.'
),
(
    'Doodhpathri Untouched Milk Valley',
    'Budgam, Kashmir',
    '2 Days / 1 Night',
    9500.00,
    'Valleys & Meadows',
    4.8,
    'https://kashmirlife.net/wp-content/uploads/2023/08/Doodpathri-1-1068x577.jpg',
    'Escape into untouched serenity. Lush velvet rolling meadows, cold foaming Shaliganga river waters, and pure alpine tranquility.'
),
(
    'Gurez Valley Offbeat Border Expedition',
    'Bandipora, Kashmir',
    '5 Days / 4 Nights',
    22500.00,
    'Offbeat Expeditions',
    5.0,
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=800&q=80',
    'Discover Kashmir hidden jewel. Marvel at the dramatic pyramid of Habba Khatoon Peak, the azure Kishanganga river, and unique Dard-Shin culture.'
),
(
    'Kashmir Great Lakes (KGL) Trek',
    'Sonamarg to Naranag',
    '7 Days / 6 Nights',
    26000.00,
    'Alpine Treks',
    5.0,
    'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?auto=format&fit=crop&w=800&q=80',
    'Indias most iconic high-altitude alpine trek. Cross Nichnai pass, camp alongside Vishansar, Gadsar, Satsar, and the twin Gangabal lakes.'
);

-- Grant full permissions to danish
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO danish;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO danish;
