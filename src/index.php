<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Jannat-e-Kashmir
| Premium Kashmir Travel Booking Experience
|--------------------------------------------------------------------------
*/

// -------------------------------------------------------------------------
// 1. Database Configuration
// -------------------------------------------------------------------------

$host = getenv('DB_HOST') ?: 'travel-db';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('POSTGRES_DB') ?: 'travel_db';
$username = getenv('POSTGRES_USER') ?: 'danish';

// Production: set POSTGRES_PASSWORD through Docker environment variables.
$password = getenv('POSTGRES_PASSWORD') ?: '';

$db_connected = false;
$booking_success = false;
$booking_ref = '';
$db_error = '';

try {
    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbname}",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $db_connected = true;
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// -------------------------------------------------------------------------
// 2. Booking Submission
// -------------------------------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'book'
) {
    $dest_id = filter_input(INPUT_POST, 'destination_id', FILTER_VALIDATE_INT) ?: 0;

    $cust_name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $travel_dt = trim($_POST['travel_date'] ?? '');

    $travelers = filter_input(
        INPUT_POST,
        'num_travelers',
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => 20
            ]
        ]
    );

    if (
        $db_connected &&
        $dest_id > 0 &&
        $cust_name !== '' &&
        filter_var($email, FILTER_VALIDATE_EMAIL) &&
        $travel_dt !== '' &&
        $travelers !== false &&
        $travelers !== null
    ) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO bookings
                (
                    destination_id,
                    customer_name,
                    email,
                    phone,
                    travel_date,
                    num_travelers
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $dest_id,
                $cust_name,
                $email,
                $phone,
                $travel_dt,
                $travelers
            ]);

            $booking_success = true;
            $booking_ref = 'KMR-' . strtoupper(
                substr(md5(uniqid((string) mt_rand(), true)), 0, 6)
            );
        } catch (PDOException $e) {
            $db_error = 'Unable to complete the booking right now.';
        }
    }
}

// -------------------------------------------------------------------------
// 3. Search & Filtering
// -------------------------------------------------------------------------

$selected_cat = isset($_GET['category'])
    ? trim($_GET['category'])
    : 'All';

$search_query = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$destinations = [];

if ($db_connected) {
    $sql = "SELECT * FROM destinations WHERE 1=1";
    $params = [];

    if ($selected_cat !== 'All' && $selected_cat !== '') {
        $sql .= " AND category = ?";
        $params[] = $selected_cat;
    }

    if ($search_query !== '') {
        $sql .= "
            AND (
                name ILIKE ?
                OR location ILIKE ?
                OR description ILIKE ?
            )
        ";

        $search_term = "%{$search_query}%";

        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    $sql .= " ORDER BY price ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $destinations = $stmt->fetchAll();
}

// -------------------------------------------------------------------------
// 4. Helpers
// -------------------------------------------------------------------------

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function categoryUrl(string $category): string
{
    return 'index.php?category=' . urlencode($category);
}

function searchUrl(string $query): string
{
    return 'index.php?search=' . urlencode($query);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Discover curated Kashmir journeys, luxury houseboats, alpine adventures and unforgettable Himalayan experiences with Jannat-e-Kashmir."
    >

    <title>
        Jannat-e-Kashmir | Discover Paradise
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        /* ================================================================
           DESIGN TOKENS
        ================================================================ */

        :root {
            --forest-950: #0d211b;
            --forest-900: #102a24;
            --forest-800: #173b32;
            --forest-700: #245746;
            --forest-600: #2f6b4f;

            --saffron: #d99a32;
            --saffron-dark: #b77b20;

            --snow: #f8f7f2;
            --white: #ffffff;

            --stone-900: #26302c;
            --stone-700: #505b55;
            --stone-500: #737c76;
            --stone-300: #d8ddd8;
            --stone-200: #e7eae6;

            --success: #287d52;
            --danger: #a83d35;

            --radius-sm: 10px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --radius-xl: 32px;

            --shadow-sm:
                0 4px 18px rgba(16, 42, 36, 0.08);

            --shadow-md:
                0 14px 40px rgba(16, 42, 36, 0.12);

            --shadow-lg:
                0 28px 80px rgba(16, 42, 36, 0.18);

            --transition:
                220ms cubic-bezier(.2,.8,.2,1);
        }

        /* ================================================================
           RESET
        ================================================================ */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--snow);
            color: var(--stone-900);
            line-height: 1.6;
            overflow-x: hidden;
        }

        button,
        input {
            font: inherit;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        a {
            color: inherit;
        }

        img {
            max-width: 100%;
            display: block;
        }

        :focus-visible {
            outline: 3px solid var(--saffron);
            outline-offset: 3px;
        }

        /* ================================================================
           NAVIGATION
        ================================================================ */

        .site-nav {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 24px;
        }

        .nav-inner {
            max-width: 1240px;
            margin: auto;

            display: flex;
            align-items: center;
            justify-content: space-between;

            color: white;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;

            text-decoration: none;
            color: white;
        }

        .brand-mark {
            width: 44px;
            height: 44px;

            display: grid;
            place-items: center;

            border-radius: 50%;

            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.3);

            backdrop-filter: blur(12px);

            font-size: 21px;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-links a {
            color: rgba(255,255,255,.88);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color var(--transition);
        }

        .nav-links a:hover {
            color: white;
        }

        .nav-cta {
            background: white !important;
            color: var(--forest-900) !important;

            padding: 11px 18px;
            border-radius: 999px;

            font-weight: 700 !important;
        }

        .mobile-menu {
            display: none;

            border: 0;
            background: rgba(255,255,255,.14);
            color: white;

            width: 42px;
            height: 42px;
            border-radius: 50%;

            cursor: pointer;
        }

        /* ================================================================
           HERO
        ================================================================ */

        .hero {
            position: relative;
            min-height: 760px;

            display: flex;
            align-items: center;

            overflow: hidden;

            background:
                linear-gradient(
                    180deg,
                    rgba(5, 21, 16, .42),
                    rgba(5, 21, 16, .68)
                ),
                url('https://images.unsplash.com/photo-1605649487212-47bdab064df7?auto=format&fit=crop&w=2200&q=90')
                center / cover;
        }

        .hero::after {
            content: '';

            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;

            height: 180px;

            background:
                linear-gradient(
                    transparent,
                    var(--snow)
                );
        }

        .hero-content {
            position: relative;
            z-index: 2;

            width: min(1240px, calc(100% - 48px));

            margin: 100px auto 0;

            color: white;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            margin-bottom: 20px;

            font-size: 12px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 1.8px;
        }

        .eyebrow::before {
            content: '';

            width: 32px;
            height: 2px;

            background: var(--saffron);
        }

        .hero h1 {
            max-width: 760px;

            font-family: 'DM Serif Display', serif;

            font-size: clamp(54px, 7vw, 92px);
            line-height: .98;
            letter-spacing: -2px;

            margin-bottom: 24px;
        }

        .hero h1 em {
            color: #f2c86a;
            font-style: normal;
        }

        .hero-description {
            max-width: 590px;

            color: rgba(255,255,255,.84);

            font-size: 18px;
            line-height: 1.7;

            margin-bottom: 32px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 50px;

            padding: 0 22px;

            border-radius: 999px;

            border: 0;

            cursor: pointer;

            text-decoration: none;

            font-size: 14px;
            font-weight: 700;

            transition:
                transform var(--transition),
                box-shadow var(--transition),
                background var(--transition);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--saffron);
            color: var(--forest-950);

            box-shadow:
                0 10px 25px rgba(217,154,50,.24);
        }

        .btn-primary:hover {
            background: #e5aa43;
        }

        .btn-light {
            color: white;

            background: rgba(255,255,255,.12);

            border: 1px solid rgba(255,255,255,.35);

            backdrop-filter: blur(12px);
        }

        /* ================================================================
           SEARCH PANEL
        ================================================================ */

        .search-panel {
            position: relative;
            z-index: 5;

            width: min(1120px, calc(100% - 48px));

            margin: -82px auto 0;

            background: white;

            border-radius: var(--radius-lg);

            box-shadow: var(--shadow-lg);

            padding: 10px;

            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;

            gap: 4px;
        }

        .search-field {
            padding: 14px 18px;
            border-right: 1px solid var(--stone-200);
        }

        .search-field:last-of-type {
            border-right: 0;
        }

        .search-label {
            display: block;

            color: var(--stone-500);

            font-size: 11px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: .8px;

            margin-bottom: 4px;
        }

        .search-value {
            color: var(--stone-900);

            font-size: 14px;
            font-weight: 600;
        }

        .search-submit {
            min-width: 130px;

            border: 0;
            border-radius: 18px;

            background: var(--forest-800);
            color: white;

            cursor: pointer;

            font-weight: 700;

            transition: background var(--transition);
        }

        .search-submit:hover {
            background: var(--forest-700);
        }

        .search-form {
            display: contents;
        }

        .search-input {
            width: 100%;

            border: 0;
            outline: 0;

            color: var(--stone-900);
            background: transparent;

            font-size: 14px;
            font-weight: 600;
        }

        .search-input::placeholder {
            color: var(--stone-500);
        }

        /* ================================================================
           MAIN
        ================================================================ */

        .main {
            max-width: 1240px;
            margin: auto;

            padding: 100px 24px;
        }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;

            gap: 30px;

            margin-bottom: 28px;
        }

        .section-eyebrow {
            color: var(--saffron-dark);

            font-size: 11px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 1.4px;

            margin-bottom: 8px;
        }

        .section-title {
            font-family: 'DM Serif Display', serif;

            color: var(--forest-900);

            font-size: clamp(34px, 4vw, 52px);

            line-height: 1.05;
        }

        .section-copy {
            max-width: 440px;

            color: var(--stone-500);

            font-size: 14px;
        }

        /* ================================================================
           FILTERS
        ================================================================ */

        .filters {
            display: flex;
            gap: 8px;

            overflow-x: auto;

            padding-bottom: 10px;
            margin-bottom: 30px;

            scrollbar-width: none;
        }

        .filters::-webkit-scrollbar {
            display: none;
        }

        .filter {
            flex: 0 0 auto;

            padding: 9px 18px;

            border: 1px solid #DCE5DF;

            border-radius: 8px;

            background: white;

            color: #3B4E47;

            text-decoration: none;

            font-size: 13px;
            font-weight: 600;

            transition: all 0.2s ease;
        }

        .filter:hover {
            border-color: #16322A;
            color: #16322A;
            background: #F8FAF8;
        }

        .filter.active {
            background: #16322A;
            border-color: #16322A;
            color: #F6C479;
        }

        /* ================================================================
           PACKAGE GRID & CARDS (BESPOKE EDITORIAL DESIGN)
        ================================================================ */

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;
        }

        .package-card {
            background: #FFFFFF;
            border: 1px solid #E5EBE6;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(16, 42, 36, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), 
                        box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1), 
                        border-color 0.25s ease;
        }

        .package-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px rgba(16, 42, 36, 0.08);
            border-color: #CBD8CE;
        }

        .package-image {
            position: relative;
            height: 240px;
            overflow: hidden;
            background: #EBEFEA;
        }

        .package-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 600ms cubic-bezier(0.16, 1, 0.3, 1);
        }

        .package-card:hover .package-image img {
            transform: scale(1.05);
        }

        .package-image-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(16, 42, 36, 0.15) 0%, rgba(16, 42, 36, 0) 40%, rgba(16, 42, 36, 0.45) 100%);
            pointer-events: none;
        }

        .category-badge {
            position: absolute;
            left: 14px;
            top: 14px;
            padding: 5px 11px;
            border-radius: 6px;
            background: rgba(16, 42, 36, 0.85);
            color: #F6C479;
            backdrop-filter: blur(8px);
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border: 1px solid rgba(246, 196, 121, 0.25);
        }

        .duration-badge {
            position: absolute;
            left: 14px;
            bottom: 12px;
            padding: 4px 9px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.92);
            color: #16322A;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            backdrop-filter: blur(6px);
        }

        .package-body {
            padding: 22px 22px 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .package-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 11.5px;
            color: #6C7A74;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .meta-dot {
            color: #A3B2AA;
        }

        .package-rating {
            color: #B87A28;
            font-weight: 700;
        }

        .package-title {
            color: #142E25;
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 21px;
            line-height: 1.25;
            margin: 0 0 10px 0;
            font-weight: 400;
        }

        .package-description {
            color: #556660;
            font-size: 13.5px;
            line-height: 1.6;
            margin: 0 0 16px 0;
            flex-grow: 1;
        }

        .package-perks {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
        }

        .perk-tag {
            background: #F1F5F2;
            color: #234339;
            border: 1px solid #DCE5DF;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .package-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid #EBEFEA;
            margin-top: auto;
        }

        .price-box {
            display: flex;
            flex-direction: column;
        }

        .price-prefix {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #798781;
        }

        .price-val {
            color: #142E25;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .price-suffix {
            font-size: 11px;
            color: #798781;
            font-weight: 500;
        }

        .book-btn {
            border: 1px solid rgba(22, 50, 42, 0.2);
            background: #16322A;
            color: #F6C479;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.25s ease;
        }

        .book-btn:hover {
            background: #0E221C;
            color: #FFFFFF;
            border-color: #F6C479;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(22, 50, 42, 0.2);
        }

        .btn-arrow {
            transition: transform 0.25s ease;
        }

        .book-btn:hover .btn-arrow {
            transform: translateX(3px);
        }

        /* ================================================================
           EMPTY STATE
        ================================================================ */

        .empty-state {
            grid-column: 1 / -1;

            padding: 70px 20px;

            text-align: center;

            background: white;

            border: 1px solid var(--stone-200);
            border-radius: var(--radius-lg);
        }

        .empty-icon {
            font-size: 38px;
            margin-bottom: 12px;
        }

        .empty-state h3 {
            color: var(--forest-900);
            margin-bottom: 5px;
        }

        .empty-state p {
            color: var(--stone-500);
            font-size: 14px;
        }

        /* ================================================================
           EXPERIENCE SECTION
        ================================================================ */

        .experiences {
            background: var(--forest-900);
            color: white;

            padding: 90px 24px;
        }

        .experience-inner {
            max-width: 1240px;
            margin: auto;
        }

        .experiences .section-title {
            color: white;
        }

        .experiences .section-eyebrow {
            color: #e9b75d;
        }

        .experience-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 16px;

            margin-top: 36px;
        }

        .experience-card {
            min-height: 270px;

            padding: 24px;

            border-radius: var(--radius-lg);

            display: flex;
            flex-direction: column;
            justify-content: end;

            position: relative;
            overflow: hidden;

            background:
                linear-gradient(
                    180deg,
                    transparent 20%,
                    rgba(0,0,0,.8)
                ),
                var(--image) center / cover;
        }

        .experience-card h3 {
            font-family: 'DM Serif Display', serif;
            font-size: 27px;
            margin-bottom: 5px;
        }

        .experience-card p {
            color: rgba(255,255,255,.76);
            font-size: 12px;
        }

        /* ================================================================
           TRUST / CTA
        ================================================================ */

        .trust-section {
            max-width: 1240px;

            margin: auto;

            padding: 90px 24px;
        }

        .trust-grid {
            display: grid;

            grid-template-columns: 1.1fr .9fr;

            gap: 60px;

            align-items: center;
        }

        .trust-title {
            font-family: 'DM Serif Display', serif;

            color: var(--forest-900);

            font-size: clamp(38px, 5vw, 62px);

            line-height: 1.03;

            margin-bottom: 18px;
        }

        .trust-copy {
            color: var(--stone-500);

            max-width: 560px;

            font-size: 15px;
            line-height: 1.8;

            margin-bottom: 25px;
        }

        .trust-points {
            display: grid;
            gap: 14px;
        }

        .trust-point {
            display: flex;
            align-items: center;
            gap: 12px;

            color: var(--stone-700);

            font-size: 13px;
            font-weight: 600;
        }

        .check {
            width: 28px;
            height: 28px;

            display: grid;
            place-items: center;

            border-radius: 50%;

            background: #e6f1eb;
            color: var(--success);

            font-size: 13px;
        }

        .trust-image {
            height: 470px;

            border-radius: var(--radius-xl);

            background:
                url('https://images.unsplash.com/photo-1595815771614-ade9d652a65d?auto=format&fit=crop&w=1000&q=85')
                center / cover;

            box-shadow: var(--shadow-lg);
        }

        /* ================================================================
           BOOKING SUCCESS
        ================================================================ */

        .success-banner {
            max-width: 1240px;

            margin: 30px auto 0;
            padding: 0 24px;
        }

        .success-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            padding: 20px 22px;

            border-radius: var(--radius-md);

            background: #eaf6ee;
            border: 1px solid #c8e6d3;

            color: var(--forest-900);
        }

        .success-inner strong {
            display: block;
            margin-bottom: 2px;
        }

        .success-ref {
            color: var(--success);
            font-size: 13px;
        }

        .dismiss {
            border: 0;
            background: transparent;

            color: var(--stone-700);

            font-size: 22px;

            cursor: pointer;
        }

        /* ================================================================
           MODAL
        ================================================================ */

        .modal {
            position: fixed;

            inset: 0;

            z-index: 1000;

            display: none;
            align-items: center;
            justify-content: center;

            padding: 20px;

            background: rgba(5,21,16,.72);

            backdrop-filter: blur(10px);
        }

        .modal.active {
            display: flex;
        }

        .modal-card {
            width: min(560px, 100%);

            max-height: calc(100vh - 40px);

            overflow-y: auto;

            background: var(--snow);

            border-radius: var(--radius-xl);

            box-shadow: 0 40px 100px rgba(0,0,0,.3);

            padding: 30px;
        }

        .modal-header {
            display: flex;
            align-items: start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }

        .modal-eyebrow {
            color: var(--saffron-dark);

            font-size: 10px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 1px;

            margin-bottom: 5px;
        }

        .modal-title {
            color: var(--forest-900);

            font-family: 'DM Serif Display', serif;

            font-size: 34px;
            line-height: 1.1;
        }

        .modal-close {
            flex: 0 0 auto;

            width: 38px;
            height: 38px;

            border: 0;
            border-radius: 50%;

            background: var(--stone-200);

            cursor: pointer;

            font-size: 20px;
        }

        .selected-package {
            padding: 16px;

            border-radius: var(--radius-md);

            background: white;

            border: 1px solid var(--stone-200);

            margin-bottom: 24px;
        }

        .selected-package-name {
            color: var(--forest-800);
            font-weight: 800;
            font-size: 14px;
        }

        .selected-package-note {
            color: var(--stone-500);
            font-size: 12px;
            margin-top: 3px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-label {
            display: block;

            color: var(--stone-700);

            font-size: 11px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: .5px;

            margin-bottom: 7px;
        }

        .form-input {
            width: 100%;

            min-height: 48px;

            padding: 0 14px;

            border: 1px solid var(--stone-300);

            border-radius: 12px;

            background: white;

            color: var(--stone-900);

            outline: none;

            transition: border var(--transition);
        }

        .form-input:focus {
            border-color: var(--forest-600);
        }

        .modal-actions {
            display: flex;
            gap: 10px;

            margin-top: 24px;
        }

        .modal-actions .btn {
            flex: 1;
        }

        .btn-secondary {
            background: var(--stone-200);
            color: var(--stone-700);
        }

        /* ================================================================
           FOOTER
        ================================================================ */

        footer {
            background: var(--forest-950);
            color: white;

            padding: 55px 24px 25px;
        }

        .footer-inner {
            max-width: 1240px;
            margin: auto;
        }

        .footer-top {
            display: flex;
            justify-content: space-between;
            gap: 40px;

            padding-bottom: 45px;

            border-bottom: 1px solid rgba(255,255,255,.1);
        }

        .footer-brand {
            max-width: 400px;
        }

        .footer-brand h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .footer-brand p {
            color: rgba(255,255,255,.58);
            font-size: 13px;
        }

        .footer-links {
            display: flex;
            gap: 40px;
        }

        .footer-links a {
            color: rgba(255,255,255,.7);

            text-decoration: none;

            font-size: 13px;

            transition: color var(--transition);
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            padding-top: 22px;

            color: rgba(255,255,255,.38);

            font-size: 11px;
        }

        /* ================================================================
           RESPONSIVE
        ================================================================ */

        @media (max-width: 1000px) {

            .packages-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .experience-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .trust-grid {
                grid-template-columns: 1fr;
            }

            .trust-image {
                height: 360px;
            }

            .search-panel {
                grid-template-columns: 1fr 1fr;
            }

            .search-field {
                border-right: 0;
                border-bottom: 1px solid var(--stone-200);
            }

            .search-submit {
                min-height: 50px;
            }
        }

        @media (max-width: 700px) {

            .site-nav {
                padding: 16px;
            }

            .nav-links {
                display: none;
            }

            .mobile-menu {
                display: block;
            }

            .hero {
                min-height: 700px;
            }

            .hero-content {
                width: min(100% - 32px, 1240px);
                margin-top: 80px;
            }

            .hero h1 {
                font-size: 54px;
                letter-spacing: -1px;
            }

            .hero-description {
                font-size: 15px;
            }

            .hero-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-actions .btn {
                width: 100%;
            }

            .search-panel {
                width: calc(100% - 32px);

                margin-top: -60px;

                grid-template-columns: 1fr;
            }

            .search-field {
                border-bottom: 1px solid var(--stone-200);
            }

            .search-submit {
                min-height: 52px;
            }

            .main {
                padding: 75px 16px;
            }

            .section-heading {
                display: block;
            }

            .section-copy {
                margin-top: 12px;
            }

            .packages-grid {
                grid-template-columns: 1fr;
            }

            .experience-grid {
                grid-template-columns: 1fr;
            }

            .experience-card {
                min-height: 230px;
            }

            .trust-section {
                padding: 70px 16px;
            }

            .trust-image {
                height: 300px;
            }

            .footer-top {
                display: block;
            }

            .footer-links {
                margin-top: 25px;
                flex-wrap: wrap;
                gap: 15px 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-card {
                padding: 22px;
                border-radius: 22px;
            }

            .modal-title {
                font-size: 30px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                transition: none !important;
                animation: none !important;
            }
        }

    </style>

</head>

<body>

<!-- ================================================================
     NAVIGATION
================================================================ -->

<nav class="site-nav">

    <div class="nav-inner">

        <a href="index.php" class="brand">

            <span class="brand-mark" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 19L9.5 7L13.5 13.5L16.5 9L21.5 19H2.5Z" stroke="#F6C479" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.5 7L11.5 10.5L8 12L9.5 7Z" fill="#F6C479"/>
                    <path d="M16.5 9L17.8 11.2L15.2 12L16.5 9Z" fill="#F6C479"/>
                </svg>
            </span>

            <span class="brand-name">
                Jannat-e-Kashmir
            </span>

        </a>

        <div class="nav-links">

            <a href="#packages">
                Destinations
            </a>

            <a href="#experiences">
                Experiences
            </a>

            <a href="#about">
                About
            </a>

            <a href="#packages" class="nav-cta">
                Plan Your Trip
            </a>

        </div>

        <button
            class="mobile-menu"
            type="button"
            aria-label="Open navigation menu"
        >
            ☰
        </button>

    </div>

</nav>


<!-- ================================================================
     HERO
================================================================ -->

<header class="hero">

    <div class="hero-content">

        <div class="eyebrow">
            Heaven on Earth · Kashmir
        </div>

        <h1>
            Find your way to
            <em>paradise.</em>
        </h1>

        <p class="hero-description">
            Discover carefully curated journeys through Kashmir's
            mountains, lakes, meadows and timeless valleys.
        </p>

        <div class="hero-actions">

            <a
                href="#packages"
                class="btn btn-primary"
            >
                Explore Kashmir
                <span style="margin-left:8px;">→</span>
            </a>

            <a
                href="#experiences"
                class="btn btn-light"
            >
                Discover experiences
            </a>

        </div>

    </div>

</header>


<!-- ================================================================
     SEARCH
================================================================ -->

<section class="search-panel" aria-label="Search Kashmir packages">

    <form
        action="index.php"
        method="GET"
        class="search-form"
    >

        <div class="search-field">

            <span class="search-label">
                Destination
            </span>

            <input
                class="search-input"
                type="text"
                name="search"
                value="<?= e($search_query) ?>"
                placeholder="Gulmarg, Pahalgam..."
                aria-label="Search destination"
            >

        </div>

        <div class="search-field">

            <span class="search-label">
                Experience
            </span>

            <span class="search-value">
                Kashmir journeys
            </span>

        </div>

        <div class="search-field">

            <span class="search-label">
                Best for
            </span>

            <span class="search-value">
                Families · Couples · Adventure
            </span>

        </div>

        <button
            class="search-submit"
            type="submit"
        >
            Search Kashmir
        </button>

    </form>

</section>


<?php if ($booking_success): ?>

    <div class="success-banner">

        <div class="success-inner">

            <div>

                <strong>
                    Your Kashmir journey is confirmed.
                </strong>

                <span class="success-ref">
                    Booking reference:
                    <strong style="display:inline;">
                        <?= e($booking_ref) ?>
                    </strong>
                </span>

            </div>

            <button
                class="dismiss"
                type="button"
                onclick="this.parentElement.parentElement.remove()"
                aria-label="Dismiss notification"
            >
                ×
            </button>

        </div>

    </div>

<?php endif; ?>


<!-- ================================================================
     PACKAGES
================================================================ -->

<main class="main" id="packages">

    <div class="section-heading">

        <div>

            <div class="section-eyebrow">
                Curated journeys
            </div>

            <h2 class="section-title">
                Explore Kashmir
            </h2>

        </div>

        <p class="section-copy">
            From snow-covered Gulmarg to peaceful houseboats
            on Dal Lake, find a journey made for you.
        </p>

    </div>


    <!-- Filters -->

    <div class="filters" aria-label="Package categories">

        <?php
        $categories = [
            'All' => 'All journeys',
            'Winter Sports' => 'Winter',
            'Lakes & Houseboats' => 'Lakes & Houseboats',
            'Valleys & Meadows' => 'Valleys',
            'Alpine Treks' => 'Treks',
            'Offbeat Expeditions' => 'Offbeat'
        ];
        ?>

        <?php foreach ($categories as $category => $label): ?>

            <a
                href="<?= $category === 'All'
                    ? 'index.php'
                    : e(categoryUrl($category)) ?>"
                class="filter <?= $selected_cat === $category ? 'active' : '' ?>"
            >
                <?= e($label) ?>
            </a>

        <?php endforeach; ?>

    </div>


    <!-- Package Cards -->

    <div class="packages-grid">

        <?php if (empty($destinations)): ?>

            <div class="empty-state">

                <div class="empty-icon">
                    🏔️
                </div>

                <h3>
                    No journeys found
                </h3>

                <p>
                    Try another destination or explore all Kashmir packages.
                </p>

                <br>

                <a
                    href="index.php"
                    class="btn btn-primary"
                >
                    View all journeys
                </a>

            </div>

        <?php else: ?>

            <?php
            $highlightsMap = [
                1 => ['Phase 2 Gondola Pass', 'Apharwat Ski Trail', 'Pine Resort Stay'],
                2 => ['Heritage Cedar Houseboat', 'Private Sunset Shikara', 'Wazwan Dinner'],
                3 => ['Betaab & Aru Valley', 'Baisaran Meadow Walk', 'Lidder River Trail'],
                4 => ['Thajiwas Glacier Trek', 'Sindh River Camp', 'Mountain Guide'],
                5 => ['Shaliganga Stream Walk', 'Virgin Pine Forest', 'Alpine Meadow Trails'],
                6 => ['Habba Khatoon Peak', 'Kishanganga River Valley', 'Dawar Heritage Stay'],
                7 => ['7 Alpine Lakes Circuit', 'Nichnai & Gadsar Passes', 'Camping Gear Included'],
            ];
            ?>

            <?php foreach ($destinations as $dest): ?>

                <article class="package-card">

                    <div class="package-image">

                        <img
                            src="<?= e($dest['image_url']) ?>"
                            alt="<?= e($dest['name']) ?>"
                            loading="lazy"
                        >

                        <div class="package-image-overlay"></div>

                        <span class="category-badge">
                            <?= e($dest['category']) ?>
                        </span>

                        <span class="duration-badge">
                            <?= e($dest['duration']) ?>
                        </span>

                    </div>


                    <div class="package-body">

                        <div class="package-meta">
                            <span class="package-loc"><?= e($dest['location']) ?></span>
                            <span class="meta-dot">·</span>
                            <span class="package-rating">★ <?= e($dest['rating']) ?></span>
                        </div>

                        <h3 class="package-title">
                            <?= e($dest['name']) ?>
                        </h3>

                        <p class="package-description">
                            <?= e($dest['description']) ?>
                        </p>

                        <?php if (!empty($highlightsMap[$dest['id']])): ?>
                            <div class="package-perks">
                                <?php foreach ($highlightsMap[$dest['id']] as $perk): ?>
                                    <span class="perk-tag"><?= e($perk) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="package-footer">

                            <div class="price-box">
                                <span class="price-prefix">From</span>
                                <span class="price-val">₹<?= number_format((float) $dest['price']) ?></span>
                                <span class="price-suffix">/ traveler</span>
                            </div>

                            <button
                                class="book-btn"
                                type="button"
                                onclick='openBooking(
                                    <?= (int) $dest['id'] ?>,
                                    <?= json_encode($dest['name']) ?>
                                )'
                            >
                                Reserve Tour <span class="btn-arrow">→</span>
                            </button>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>


<!-- ================================================================
     EXPERIENCES
================================================================ -->

<section class="experiences" id="experiences">

    <div class="experience-inner">

        <div class="section-heading">

            <div>

                <div class="section-eyebrow">
                    Experience Kashmir
                </div>

                <h2 class="section-title">
                    Travel your way.
                </h2>

            </div>

            <p class="section-copy" style="color:rgba(255,255,255,.6);">
                Whether you come for snow, serenity or adventure,
                Kashmir has a story waiting for you.
            </p>

        </div>


        <div class="experience-grid">

            <div
                class="experience-card"
                style="--image:url('https://images.unsplash.com/photo-1517825738774-7de9363ef735?auto=format&fit=crop&w=900&q=85')"
            >

                <h3>
                    Winter
                </h3>

                <p>
                    Snow, skiing and Himalayan adventures.
                </p>

            </div>


            <div
                class="experience-card"
                style="--image:url('https://images.unsplash.com/photo-1598091383021-15ddea10925d?auto=format&fit=crop&w=900&q=85')"
            >

                <h3>
                    Valleys
                </h3>

                <p>
                    Meadows, mountains and peaceful escapes.
                </p>

            </div>


            <div
                class="experience-card"
                style="--image:url('https://images.unsplash.com/photo-1566837497312-7be4d8d7c3f9?auto=format&fit=crop&w=900&q=85')"
            >

                <h3>
                    Lakes
                </h3>

                <p>
                    Houseboats and unforgettable sunsets.
                </p>

            </div>


            <div
                class="experience-card"
                style="--image:url('https://images.unsplash.com/photo-1548013146-72479768bada?auto=format&fit=crop&w=900&q=85')"
            >

                <h3>
                    Adventure
                </h3>

                <p>
                    Treks and offbeat Himalayan journeys.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- ================================================================
     ABOUT / TRUST
================================================================ -->

<section class="trust-section" id="about">

    <div class="trust-grid">

        <div>

            <div class="section-eyebrow">
                Your Kashmir story
            </div>

            <h2 class="trust-title">
                More than a trip.
                A memory you'll keep.
            </h2>

            <p class="trust-copy">
                Jannat-e-Kashmir brings together thoughtfully selected
                destinations and experiences so you can spend less time
                planning and more time experiencing the valley.
            </p>

            <div class="trust-points">

                <div class="trust-point">

                    <span class="check">✓</span>

                    Carefully selected Kashmir experiences

                </div>

                <div class="trust-point">

                    <span class="check">✓</span>

                    Clear package pricing

                </div>

                <div class="trust-point">

                    <span class="check">✓</span>

                    Simple booking process

                </div>

                <div class="trust-point">

                    <span class="check">✓</span>

                    Journeys designed around you

                </div>

            </div>

        </div>


        <div
            class="trust-image"
            role="img"
            aria-label="Kashmir mountain landscape"
        ></div>

    </div>

</section>


<!-- ================================================================
     BOOKING MODAL
================================================================ -->

<div
    class="modal"
    id="bookingModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="bookingTitle"
    aria-hidden="true"
>

    <div class="modal-card">

        <div class="modal-header">

            <div>

                <div class="modal-eyebrow">
                    Plan your journey
                </div>

                <h2
                    class="modal-title"
                    id="bookingTitle"
                >
                    Book Kashmir
                </h2>

            </div>

            <button
                class="modal-close"
                type="button"
                onclick="closeBooking()"
                aria-label="Close booking dialog"
            >
                ×
            </button>

        </div>


        <div class="selected-package">

            <div
                class="selected-package-name"
                id="modalPkgName"
            >
                Selected journey
            </div>

            <div class="selected-package-note">
                Tell us when you'd like to experience Kashmir.
            </div>

        </div>


        <form
            action="index.php"
            method="POST"
        >

            <input
                type="hidden"
                name="action"
                value="book"
            >

            <input
                type="hidden"
                name="destination_id"
                id="modalDestId"
                value=""
            >


            <div class="form-group">

                <label
                    class="form-label"
                    for="customerName"
                >
                    Full name
                </label>

                <input
                    id="customerName"
                    class="form-input"
                    type="text"
                    name="customer_name"
                    placeholder="Your full name"
                    autocomplete="name"
                    required
                >

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label
                        class="form-label"
                        for="email"
                    >
                        Email
                    </label>

                    <input
                        id="email"
                        class="form-input"
                        type="email"
                        name="email"
                        placeholder="you@example.com"
                        autocomplete="email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label
                        class="form-label"
                        for="phone"
                    >
                        Phone
                    </label>

                    <input
                        id="phone"
                        class="form-input"
                        type="tel"
                        name="phone"
                        placeholder="+91 9876543210"
                        autocomplete="tel"
                        required
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label
                        class="form-label"
                        for="travelDate"
                    >
                        Travel date
                    </label>

                    <input
                        id="travelDate"
                        class="form-input"
                        type="date"
                        name="travel_date"
                        required
                    >

                </div>


                <div class="form-group">

                    <label
                        class="form-label"
                        for="travelers"
                    >
                        Travelers
                    </label>

                    <input
                        id="travelers"
                        class="form-input"
                        type="number"
                        name="num_travelers"
                        min="1"
                        max="20"
                        value="1"
                        required
                    >

                </div>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeBooking()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Confirm booking →
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ================================================================
     FOOTER
================================================================ -->

<footer>

    <div class="footer-inner">

        <div class="footer-top">

            <div class="footer-brand">

                <h2>
                    Jannat-e-Kashmir
                </h2>

                <p>
                    Curated journeys through the mountains,
                    valleys and lakes of Kashmir.
                </p>

            </div>


            <div class="footer-links">

                <a href="#packages">
                    Destinations
                </a>

                <a href="#experiences">
                    Experiences
                </a>

                <a href="#about">
                    About
                </a>

                <a href="#packages">
                    Plan a trip
                </a>

            </div>

        </div>


        <div class="footer-bottom">

            © <?= date('Y') ?> Jannat-e-Kashmir · Multi-Container Architecture via Docker Compose · 
            <a href="http://localhost:8081" target="_blank" rel="noopener" style="color:#F6C479; text-decoration:underline; font-weight:500;">
                Database Adminer (Port 8081) ↗
            </a>

        </div>

    </div>

</footer>


<!-- ================================================================
     JAVASCRIPT
================================================================ -->

<script>

    const modal = document.getElementById('bookingModal');
    const destinationInput = document.getElementById('modalDestId');
    const packageName = document.getElementById('modalPkgName');
    const travelDate = document.getElementById('travelDate');

    let lastFocusedElement = null;


    function openBooking(id, name) {

        lastFocusedElement = document.activeElement;

        destinationInput.value = id;

        packageName.textContent = name;

        modal.classList.add('active');

        modal.setAttribute('aria-hidden', 'false');

        document.body.style.overflow = 'hidden';

        const today = new Date();

        const year = today.getFullYear();

        const month = String(
            today.getMonth() + 1
        ).padStart(2, '0');

        const day = String(
            today.getDate()
        ).padStart(2, '0');

        travelDate.min =
            `${year}-${month}-${day}`;

        setTimeout(() => {

            document.getElementById(
                'customerName'
            ).focus();

        }, 50);
    }


    function closeBooking() {

        modal.classList.remove('active');

        modal.setAttribute('aria-hidden', 'true');

        document.body.style.overflow = '';

        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }


    modal.addEventListener('click', function(event) {

        if (event.target === modal) {
            closeBooking();
        }

    });


    document.addEventListener('keydown', function(event) {

        if (
            event.key === 'Escape' &&
            modal.classList.contains('active')
        ) {
            closeBooking();
        }

    });


    /*
     * Basic focus trap for the booking modal.
     */

    modal.addEventListener('keydown', function(event) {

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = modal.querySelectorAll(
            'button, input, select, textarea, a[href]'
        );

        if (!focusable.length) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {

            event.preventDefault();
            last.focus();

        } else if (
            !event.shiftKey &&
            document.activeElement === last
        ) {

            event.preventDefault();
            first.focus();

        }

    });


    /*
     * Mobile navigation.
     */

    const mobileMenu =
        document.querySelector('.mobile-menu');

    const navLinks =
        document.querySelector('.nav-links');

    mobileMenu.addEventListener('click', function() {

        const isOpen =
            navLinks.style.display === 'flex';

        navLinks.style.display =
            isOpen ? '' : 'flex';

        if (!isOpen) {

            navLinks.style.position = 'absolute';
            navLinks.style.top = '70px';
            navLinks.style.right = '16px';
            navLinks.style.left = '16px';
            navLinks.style.padding = '18px';

            navLinks.style.flexDirection = 'column';
            navLinks.style.alignItems = 'stretch';

            navLinks.style.background =
                'rgba(16,42,36,.96)';

            navLinks.style.borderRadius = '18px';

        }

    });


    /*
     * Close mobile navigation after clicking a link.
     */

    document.querySelectorAll('.nav-links a')
        .forEach(function(link) {

            link.addEventListener('click', function() {

                if (window.innerWidth <= 700) {
                    navLinks.style.display = '';
                }

            });

        });

</script>

</body>
</html>
