<?php
session_start();
ob_start();
include("config.php");
include("navbar.php");

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];
$success_message = '';
$error_message = ''; // To store any error messages

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle address removal
if (isset($_POST['remove_address_id'])) {
    $remove_address_id = $_POST['remove_address_id'];
    $sql_remove_address = "DELETE FROM receiver_address WHERE receiverAddress_id = ? AND receiver_id IN (SELECT receiver_id FROM receiver WHERE userid = ?)";
    $stmt_remove = $conn->prepare($sql_remove_address);
    $stmt_remove->bind_param("ii", $remove_address_id, $userid);
    $stmt_remove->execute();
}

// Fetch cart items
$sql_cart = "SELECT cart.cart_id, cart.cart_productid, cart.cart_quantity, 
                    products.productname, products.price AS product_price, 
                    products.image AS product_image, products.quantity AS remaining_stock 
             FROM cart
             JOIN products ON cart.cart_productid = products.productid
             WHERE cart_userid = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $userid);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();


$total_cost = 0.0;
$cart_items = [];


if ($result_cart->num_rows > 0) {
    while ($row = $result_cart->fetch_assoc()) {
        $product_price = floatval($row['product_price']);
        $cart_quantity = floatval($row['cart_quantity']); // Cast to float to ensure decimals
        $subtotal = $product_price * $cart_quantity;
        $total_cost += $subtotal;

        // Add the formatted cart item for further use in the view
        $row['subtotal'] = number_format($subtotal, 2);
        $cart_items[] = $row;
        
    }
} else {
    header("Location: basket.php?message=Your cart is empty. Please add items to proceed.");
    exit();
}

 // This ensures decimals are kept


// Fetch saved addresses
$sql_receiver = "SELECT r.receiver_name, r.receiver_phone, ra.landmark, ra.purok_street, ra.barangay, ra.municipality, ra.receiverAddress_id 
                 FROM receiver r
                 JOIN receiver_address ra ON r.receiver_id = ra.receiver_id
                 WHERE r.userid = ?";
$stmt_receiver = $conn->prepare($sql_receiver);
$stmt_receiver->bind_param("i", $userid);
$stmt_receiver->execute();
$result_receiver = $stmt_receiver->get_result();
$addresses = [];

if ($result_receiver->num_rows > 0) {
    while ($row = $result_receiver->fetch_assoc()) {
        $addresses[] = $row;
    }
}

// Get the newly saved address ID if available
$new_address_id = isset($_GET['saved_address']) ? $_GET['saved_address'] : null;

// Limit addresses to three
$address_limit_reached = count($addresses) >= 3;

// Define the municipalities and their corresponding barangays
$municipalities = [
    'Aliaga' => [
        'Betes', 'Bibiclat', 'Bucot', 'La Purisima', 'Macabucod', 'Magsaysay', 'Pantoc','Poblacion Centro', 'Poblacion East I', 'Poblacion East II', 'Poblacion West III','Poblacion West IV', 'San Carlos', 'San Emiliano', 'San Eustacio', 'San Felipe Bata','San Felipe Matanda', 'San Juan', 'San Pablo Bata', 'San Pablo Matanda', 'Santa Monica','Santiago', 'Santo Rosario', 'Santo Tomas', 'Sunson', 'Umangan'],
    'Bongabon' => [
        'Antipolo', 'Ariendo', 'Bantug', 'Calaanan', 'Commercial', 'Cruz', 'Curva', 
        'Digmala', 'Kaingin', 'Labi', 'Larcon', 'Lusok', 'Macabaclay', 'Magtanggol', 
        'Mantile', 'Olivete', 'Palo Maria', 'Pesa', 'Rizal', 'Sampalucan', 'San Roque', 
        'Santor', 'Sinipit', 'Sisilang na Ligaya', 'Social', 'Tugatug', 'Tulay na Bato', 
        'Vega'],
    'Cabanatuan City' => [
        'Aduas Centro', 'Aduas Norte', 'Aduas Sur', 'Bagong Buhay', 'Bagong Sikat', 'Bakero', 
        'Bakod Bayan', 'Balite', 'Bangad', 'Bantug Bulalo', 'Bantug Norte', 'Barlis', 
        'Barrera District', 'Bernardo District', 'Bitas', 'Bonifacio District', 'Buliran', 
        'Caalibangbangan', 'Cabu', 'Calawagan', 'Campo Tinio', 'Caridad', 'Caudillo', 
        'Cinco-Cinco', 'City Supermarket', 'Communal', 'Cruz Roja', 'Daang Sarile', 
        'Dalampang', 'Dicarma', 'Dimasalang', 'Dionisio S. Garcia', 'Fatima', 'General Luna', 
        'Hermogenes C. Concepcion, Sr.', 'Ibabao Bana', 'Imelda District', 'Isla', 
        'Kalikid Norte', 'Kalikid Sur', 'Kapitan Pepe', 'Lagare', 'Lourdes', 'M. S. Garcia', 
        'Mabini Extension', 'Mabini Homesite', 'Macatbong', 'Magsaysay District', 
        'Magsaysay South', 'Maria Theresa', 'Matadero', 'Mayapyap Norte', 'Mayapyap Sur', 
        'Melojavilla', 'Nabao', 'Obrero', 'Padre Burgos', 'Padre Crisostomo', 'Pagas', 
        'Palagay', 'Pamaldan', 'Pangatian', 'Patalac', 'Polilio', 'Pula', 'Quezon District', 
        'Rizdelis', 'Samon', 'San Isidro', 'San Josef Norte', 'San Josef Sur', 
        'San Juan Poblacion', 'San Roque Norte', 'San Roque Sur', 'Sanbermicristi', 
        'Sangitan', 'Sangitan East', 'Santa Arcadia', 'Santo Niño', 'Sapang', 'Sumacab Este', 
        'Sumacab Norte', 'Sumacab South', 'Talipapa', 'Valdefuente', 'Valle Cruz', 
        'Vijandre District', 'Villa Ofelia-Caridad', 'Zulueta District'],
    'Cabiao' => [
        'Bagong Buhay', 'Bagong Sikat', 'Bagong Silang', 'Concepcion', 'Entablado', 
        'Maligaya', 'Natividad North', 'Natividad South', 'Palasinan', 'Polilio', 
        'San Antonio', 'San Carlos', 'San Fernando Norte', 'San Fernando Sur', 'San Gregorio', 
        'San Juan North', 'San Juan South', 'San Roque', 'San Vicente', 'Santa Ines', 
        'Santa Isabel', 'Santa Rita', 'Sinipit'],
    'Carranglan' => [
        'Bantug', 'Bunga', 'Burgos', 'Capintalan', 'D. L. Maglanoc Poblacion', 'F. C. Otic Poblacion', 
        'G. S. Rosario Poblacion', 'General Luna', 'Joson', 'Minuli', 'Piut', 'Puncan', 
        'Putlan', 'R. A. Padilla', 'Salazar', 'San Agustin', 'T. L. Padilla Poblacion'],
    'Cuyapo' => [
        'Baloy', 'Bambanaba', 'Bantug', 'Bentigan', 'Bibiclat', 'Bonifacio', 'Bued', 
        'Bulala', 'Burgos', 'Cabatuan', 'Cabileo', 'Cacapasan', 'Calancuasan Norte', 
        'Calancuasan Sur', 'Colosboa', 'Columbitin', 'Curva', 'District I', 'District II', 
        'District IV', 'District V', 'District VI', 'District VII', 'District VIII', 'Landig', 
        'Latap', 'Loob', 'Luna', 'Malbeg-Patalan', 'Malineng', 'Matindeg', 'Maycaban', 
        'Nacuralan', 'Nagmisahan', 'Paitan Norte', 'Paitan Sur', 'Piglisan', 'Pugo', 
        'Rizal', 'Sabit', 'Salagusog', 'San Antonio', 'San Jose', 'San Juan', 'Santa Clara', 
        'Santa Cruz', 'Sinimbaan', 'Tagtagumbao', 'Tutuloy', 'Ungab', 'Villaflores'],
    'Gabaldon' => [
        'Bagong Sikat', 'Bagting', 'Bantug', 'Bitulok', 'Bugnan', 'Calabasa', 'Camachile', 
        'Cuyapa', 'Ligaya', 'Macasandal', 'Malinao', 'Pantoc', 'Pinamalisan', 'Sawmill', 
        'South Poblacion', 'Tagumpay'],
    'Gapan City' => [
        'Balante', 'Bayanihan', 'Bulak', 'Bungo', 'Kapalangan', 'Mabunga', 'Maburak', 
        'Mahipon', 'Makabaclay', 'Malimba', 'Mangino', 'Marelo', 'Pambuan', 'Parcutela', 
        'Puting Tubig', 'San Lorenzo', 'San Nicolas', 'San Roque', 'San Vicente', 
        'Santa Cruz', 'Santo Cristo Norte', 'Santo Cristo Sur', 'Santo Niño'],
    'General Mamerto Natividad' => [
        'Balangkare Norte', 'Balangkare Sur', 'Balaring', 'Belen', 'Bravo', 'Burol', 
        'Kabulihan', 'Mag-asawang Sampaloc', 'Manarog', 'Mataas na Kahoy', 'Panacsac', 
        'Picaleon', 'Pinahan', 'Platero', 'Poblacion', 'Pula', 'Pulong Singkamas', 
        'Sapang Bato', 'Talabutab Norte', 'Talabutab Sur'],
    'General Tinio' => [
        'Bago', 'Concepcion', 'Nazareth', 'Padolina', 'Palale', 'Pias', 'Poblacion Central', 
        'Poblacion East', 'Poblacion West', 'Pulong Matong', 'Rio Chico', 'Sampaguita', 
        'San Pedro'],
    'Guimba' => [
        'Agcano', 'Ayos Lomboy', 'Bacayao', 'Bagong Barrio', 'Balbalino', 'Balingog East', 
        'Balingog West', 'Banitan', 'Bantug', 'Bulakid', 'Bunol', 'Caballero', 'Cabaruan', 
        'Caingin Tabing Ilog', 'Calem', 'Camiling', 'Cardinal', 'Casongsong', 'Catimon', 
        'Cavite', 'Cawayan Bugtong', 'Consuelo', 'Culong', 'Escano', 'Faigal', 'Galvan', 
        'Guiset', 'Lamorito', 'Lennec', 'Macamias', 'Macapabellag', 'Macatcatuit', 
        'Manacsac', 'Manggang Marikit', 'Maturanoc', 'Maybubon', 'Naglabrahan', 
        'Nagpandayan', 'Narvacan I', 'Narvacan II', 'Pacac', 'Partida I', 'Partida II', 
        'Pasong Inchic', 'Saint John District', 'San Agustin', 'San Andres', 'San Bernardino', 
        'San Marcelino', 'San Miguel', 'San Rafael', 'San Roque', 'Santa Ana', 
        'Santa Cruz', 'Santa Lucia', 'Santa Veronica District', 'Santo Cristo District', 
        'Saranay District', 'Sinulatan', 'Subol', 'Tampac I', 'Tampac II & III', 
        'Triala', 'Yuson'],
    'Jaen' => [
        'Calabasa', 'Dampulan', 'Don Mariano Marcos', 'Hilera', 'Imbunia', 'Imelda Poblacion', 
        'Lambakin', 'Langla', 'Magsalisi', 'Malabon-Kaingin', 'Marawa', 'Niyugan', 
        'Ocampo-Rivera District', 'Pakol', 'Pamacpacan', 'Pinanggaan', 'Putlod', 
        'San Jose', 'San Josef', 'San Pablo', 'San Roque', 'San Vicente', 'Santa Rita', 
        'Santo Tomas North', 'Santo Tomas South', 'Sapang', 'Ulanin-Pitak'],
    'Laur' => [
        'Barangay I', 'Barangay II', 'Barangay III', 'Barangay IV', 'Betania', 'Canantong', 
        'Nauzon', 'Pangarulong', 'Pinagbayanan', 'Sagana', 'San Felipe', 'San Fernando', 
        'San Isidro', 'San Josef', 'San Juan', 'San Vicente', 'Siclong'],
    'Licab' => [
        'Aquino', 'Linao', 'Poblacion Norte', 'Poblacion Sur', 'San Casimiro', 'San Cristobal', 
        'San Jose', 'San Juan', 'Santa Maria', 'Tabing Ilog', 'Villarosa'],
    'Llanera' => [
        'A. Bonifacio', 'Bagumbayan', 'Bosque', 'Caridad Norte', 'Caridad Sur', 'Casile', 
        'Florida Blanca', 'General Luna', 'General Ricarte', 'Gomez', 'Inanama', 'Ligaya', 
        'Mabini', 'Murcon', 'Plaridel', 'San Felipe', 'San Francisco', 'San Nicolas', 
        'San Vicente', 'Santa Barbara', 'Victoria', 'Villa Viniegas'],
    'Lupao' => [
        'Agupalo Este', 'Agupalo Weste', 'Alalay Chica', 'Alalay Grande', 'Bagong Flores', 
        'Balbalungao', 'Burgos', 'Cordero', 'J. U. Tienzo', 'Mapangpang', 'Namulandayan', 
        'Parista', 'Poblacion East', 'Poblacion North', 'Poblacion South', 'Poblacion West', 
        'Salvacion I', 'Salvacion II', 'San Antonio Este', 'San Antonio Weste', 
        'San Isidro', 'San Pedro', 'San Roque', 'Santo Domingo'],
    'Muñoz' => [
        'Bagong Sikat', 'Balante', 'Bantug', 'Bical', 'Cabisuculan', 'Calabalabaan', 
        'Calisitan', 'Catalanacan', 'Curva', 'Franza', 'Gabaldon', 'Labney', 'Licaong', 
        'Linglingay', 'Magtanggol', 'Maligaya', 'Mangandingay', 'Mapangpang', 'Maragol', 
        'Matingkis', 'Naglabrahan', 'Palusapis', 'Pandalla', 'Poblacion East', 
        'Poblacion North', 'Poblacion South', 'Poblacion West', 'Rang-ayan', 'Rizal', 
        'San Andres', 'San Antonio', 'San Felipe', 'Sapang Cawayan', 'Villa Cuizon', 
        'Villa Isla', 'Villa Nati', 'Villa Santos'],
    'Nampicuan' => [
        'Alemania', 'Ambasador Alzate Village', 'Cabaducan East', 'Cabaducan West', 'Cabawangan', 
        'East Central Poblacion', 'Edy', 'Estacion', 'Maeling', 'Mayantoc', 'Medico', 
        'Monic', 'North Poblacion', 'Northwest Poblacion', 'Recuerdo', 'South Central Poblacion', 
        'Southeast Poblacion', 'Southwest Poblacion', 'Tony', 'West Central Poblacion', 
        'West Poblacion'],
    'Palayan City' => [
        'Atate', 'Aulo', 'Bagong Buhay', 'Bo. Militar', 'Caballero', 'Caimito', 'Doña Josefa', 
        'Ganaderia', 'Imelda Valley', 'Langka', 'Malate', 'Maligaya', 'Manacnac', 
        'Mapait', 'Marcos Village', 'Popolon Pagas', 'Santolan', 'Sapang Buho', 'Singalat'],
    'Pantabangan' => [
        'Cadaclan', 'Cambitala', 'Conversion', 'Fatima', 'Ganduz', 'Liberty', 'Malbang', 
        'Marikit', 'Napon-Napon', 'Poblacion East', 'Poblacion West', 'Sampaloc', 
        'San Juan', 'Villarica'],
    'Peñaranda' => [
        'Callos', 'Las Piñas', 'Poblacion I', 'Poblacion II', 'Poblacion III', 'Poblacion IV', 
        'San Josef', 'San Mariano', 'Santo Tomas', 'Sinasajan'],
    'Quezon' => [
        'Barangay I', 'Barangay II', 'Bertese', 'Doña Lucia', 'Dulong Bayan', 'Ilog Baliwag', 
        'Pulong Bahay', 'San Alejandro', 'San Andres I', 'San Andres II', 'San Manuel', 
        'San Miguel', 'Santa Clara', 'Santa Rita', 'Santo Cristo', 'Santo Tomas Feria'],
    'Rizal' => [
        'Agbannawag', 'Aglipay', 'Bicos', 'Cabucbucan', 'Calaocan District', 'Canaan East', 
        'Canaan West', 'Casilagan', 'Del Pilar', 'Estrella', 'General Luna', 'Macapsing', 
        'Maligaya', 'Paco Roman', 'Pag-asa', 'Poblacion Central', 'Poblacion East', 
        'Poblacion Norte', 'Poblacion Sur', 'Poblacion West', 'Portal', 'San Esteban', 
        'San Gregorio', 'Santa Monica', 'Villa Labrador', 'Villa Paraiso'],
    'San Antonio' => [
        'Buliran', 'Cama Juan', 'Julo', 'Lawang Kupang', 'Luyos', 'Maugat', 'Panabingan', 
        'Papaya', 'Poblacion', 'San Francisco', 'San Jose', 'San Mariano', 'Santa Barbara', 
        'Santa Cruz', 'Santo Cristo', 'Tikiw'],
    'San Isidro' => [
        'Alua', 'Calaba', 'Malapit', 'Mangga', 'Poblacion', 'Pulo', 'San Roque', 
        'Santo Cristo', 'Tabon'],
    'San Leonardo' => [
        'Bonifacio District', 'Burgos District', 'Castellano', 'Diversion', 'Magpapalayoc', 
        'Mallorca', 'Mambangnan', 'Nieves', 'Rizal District', 'San Anton', 'San Bartolome', 
        'San Roque', 'Tabuating', 'Tagumpay', 'Tambo Adorable'],
    'Santa Rosa' => [
        'Aguinaldo', 'Berang', 'Burgos', 'Cojuangco', 'Del Pilar', 'Gomez', 'Inspector', 
        'Isla', 'La Fuente', 'Liwayway', 'Lourdes', 'Luna', 'Mabini', 'Malacañang', 
        'Maliolio', 'Mapalad', 'Rajal Centro', 'Rajal Norte', 'Rajal Sur', 'Rizal', 
        'San Gregorio', 'San Isidro', 'San Josep', 'San Mariano', 'San Pedro', 
        'Santa Teresita', 'Santo Rosario', 'Sapsap', 'Soledad', 'Tagpos', 'Tramo', 
        'Valenzuela', 'Zamora'],
    'Santo Domingo' => [
        'Baloc', 'Buasao', 'Burgos', 'Cabugao', 'Casulucan', 'Comitang', 'Concepcion', 
        'Dolores', 'General Luna', 'Hulo', 'Mabini', 'Malasin', 'Malaya', 'Malayantoc', 
        'Mambarao', 'Poblacion', 'Pulong Buli', 'Sagaba', 'San Agustin', 'San Fabian', 
        'San Francisco', 'San Pascual', 'Santa Rita', 'Santo Rosario'],
    'Talavera' => [
        'Andal Alino', 'Bagong Sikat', 'Bagong Silang', 'Bakal I', 'Bakal II', 'Bakal III', 
        'Baluga', 'Bantug', 'Bantug Hacienda', 'Bantug Hamog', 'Bugtong na Buli', 'Bulac', 
        'Burnay', 'Caaniplahan', 'Cabubulaonan', 'Calipahan', 'Campos', 'Caputican', 
        'Casulucan Este', 'Collado', 'Dimasalang Norte', 'Dimasalang Sur', 'Dinarayat', 
        'Esguerra District', 'Gulod', 'Homestead I', 'Homestead II', 'Kinalanguyan', 
        'La Torre', 'Lomboy', 'Mabuhay', 'Maestrang Kikay', 'Mamandil', 'Marcos District', 
        'Matingkis', 'Minabuyoc', 'Pag-asa', 'Paludpod', 'Pantoc Bulac', 'Pinagpanaan', 
        'Poblacion Sur', 'Pula', 'Pulong San Miguel', 'Purok Matias', 'Sampaloc', 
        'San Miguel na Munti', 'San Pascual', 'San Ricardo', 'Sibul', 'Sicsican Matanda', 
        'Tabacao', 'Tagaytay', 'Valle'],
    'Talugtug' => [
        'Alula', 'Baybayabas', 'Buted', 'Cabiangan', 'Calisitan', 'Cinense', 'Culiat', 
        'Maasin', 'Magsaysay', 'Mayamot I', 'Mayamot II', 'Nangabulan', 'Osmeña', 
        'Pangit', 'Patola', 'Quezon', 'Quirino', 'Roxas', 'Saguing', 'Sampaloc', 
        'Santa Catalina', 'Santo Domingo', 'Saringaya', 'Saverona', 'Tandoc', 'Tibag', 
        'Villa Boado', 'Villa Rosario'],
    'Zaragoza' => [
        'Batitang', 'Carmen', 'Concepcion', 'Del Pilar', 'General Luna', 'H. Romero', 
        'Macarse', 'Manaul', 'Mayamot', 'Pantoc', 'San Isidro', 'San Rafael', 
        'San Vicente', 'Santa Cruz', 'Santa Lucia Old', 'Santa Lucia Young', 
        'Santo Rosario Old', 'Santo Rosario Young', 'Valeriana']
];

// Handle new address submission
if (isset($_POST['save_address']) && !$address_limit_reached) {
    $receiver_name = $_POST['receiver_name'];
    $municipality = $_POST['municipality'];
    $barangay = $_POST['barangay'];
    $landmark = $_POST['landmark'];
    $purok_street = $_POST['purok_street'];
    $receiver_phone = $_POST['receiver_phone'];

    // Check for duplicate address
    $check_duplicate = "SELECT * FROM receiver_address ra 
                        JOIN receiver r ON r.receiver_id = ra.receiver_id
                        WHERE r.userid = ? AND ra.purok_street = ? AND ra.barangay = ? AND ra.municipality = ?";
    $stmt_check = $conn->prepare($check_duplicate);
    $stmt_check->bind_param("isss", $userid, $purok_street, $barangay, $municipality);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        // No duplicate found, proceed to insert into receiver and receiver_address
        $sql_insert_receiver = "INSERT INTO receiver (receiver_name, receiver_phone, userid) VALUES (?, ?, ?)";
        $stmt_insert_receiver = $conn->prepare($sql_insert_receiver);
        $stmt_insert_receiver->bind_param("ssi", $receiver_name, $receiver_phone, $userid);

        if ($stmt_insert_receiver->execute()) {
            $new_receiver_id = $conn->insert_id;

            // Insert into receiver_address
            $sql_insert_address = "INSERT INTO receiver_address (receiver_id, municipality, barangay, landmark, purok_street) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert_address = $conn->prepare($sql_insert_address);
            $stmt_insert_address->bind_param("issss", $new_receiver_id, $municipality, $barangay, $landmark, $purok_street);

            if ($stmt_insert_address->execute()) {
                // Show success message when address is added
                $success_message = 'Address added successfully!';
                header("Location: checkout.php?saved_address=" . $conn->insert_id);
                exit();
            } else {
                $error_message = "Failed to save address: " . $stmt_insert_address->error;
            }
        } else {
            $error_message = "Failed to save receiver information: " . $stmt_insert_receiver->error;
        }
    }
}

$conn->close();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Checkout Receipt</title>
    <style>
        .remove-address {
            color: red;
            background: none;
            border: none;
            cursor: pointer;
        }

        .remove-address i {
            font-size: 1.2em;
        }

        .remove-address:hover {
            opacity: 0.8;
        }


    </style>
</head>
<body>
<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo $success_message; ?>
    </div>
<?php elseif ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>
<div class="receipt-container">
    <a href="basket.php" class="next-page-button"><i class="fa-solid fa-circle-arrow-left"></i></a>
    <h2>Order Overview</h2>
    <div class="receipt-header">
        <p>NESRAC</p>
        <p>Campos Talavera Nueva Ecija</p>
    </div>
    <hr>
    <div class="receipt-body">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity (Kg):</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
           <tbody>
    <?php foreach ($cart_items as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['productname']); ?></td>
            <td>₱<?php echo number_format($item['product_price'], 2); ?></td> <!-- Display product price -->
            <td><?php echo $item['cart_quantity']; ?></td>
            <td>₱<?php echo number_format($item['product_price'] * $item['cart_quantity'], 2); ?></td> <!-- Display calculated subtotal -->
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>
        <hr>
        <p class="total"><strong>Total: ₱<?php echo number_format($total_cost, 2); ?></strong></p>
    </div>

    <h2>Shipping Information</h2>

    <?php if (count($addresses) > 0): ?>
<form action="process_order.php" method="post">
    <div class="form-group">
        <label for="saved_addresses">Select an Address (Required, Only One)</label>
        <div class="checkbox-group">
            <?php foreach ($addresses as $address): ?>
            <div class="address-item">
                <div class="address-info">
                    <?php echo htmlspecialchars($address['receiver_name'] . ', ' . $address['purok_street'] . ', ' . $address['barangay'] . ', ' . $address['municipality']); ?>
                </div>
                <div class="controls">
                    <input type="radio" name="selected_address" value="<?php echo $address['receiverAddress_id']; ?>" <?php echo ($new_address_id == $address['receiverAddress_id']) ? 'checked' : ''; ?> required>
                    <button type="button" class="remove-address" data-address-id="<?php echo $address['receiverAddress_id']; ?>"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <input type="hidden" name="total_cost" value="<?php echo $total_cost; ?>">

    <!-- Wrap the button in a div with the form-actions class -->
    <div class="form-actions">
        <button type="submit" name="payment_method" value="COD" class="confirm-checkout">Checkout</button>
    </div>
    
</form>
    <?php endif; ?>
    
    <button id="showAddressForm" class="confirm-checkout">Add New Address</button>

    <?php if (!$address_limit_reached): ?>
        <div id="new_address_form" style="display: none;">
            <br>
            <h3>Add New Shipping Address</h3>
            <form id="receiverForm" action="checkout.php" method="post">
                <div class="form-group">
                    <label for="receiver_name">Receiver Name</label>
                    <input type="text" id="receiver_name" name="receiver_name" required>
                </div>
                <div class="form-group">
                    <label for="municipality">Municipality</label>
                    <select id="municipality" name="municipality" required>
                        <option value="">Select Municipality</option>
                        <?php foreach ($municipalities as $municipality => $barangays): ?>
                            <option value="<?php echo htmlspecialchars($municipality); ?>"><?php echo htmlspecialchars($municipality); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="barangay">Barangay</label>
                    <select id="barangay" name="barangay" required>
                        <option value="">Select Barangay</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="landmark">Landmark</label>
                    <input type="text" id="landmark" name="landmark">
                </div>
                <div class="form-group">
                    <label for="purok_street">Purok/Street</label>
                    <input type="text" id="purok_street" name="purok_street" required>
                </div>
                <div class="form-group">
                    <label for="receiver_phone">Phone Number</label>
                    <input type="text" id="receiver_phone" name="receiver_phone" required>
                </div>
                <div class="actions">
                    <button type="submit" name="save_address" class="confirm-checkout">Save Address</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <p>You have reached the maximum of three saved addresses. Please remove one to add a new address.</p>
    <?php endif; ?>

</div>

<script>
    document.getElementById('showAddressForm').addEventListener('click', function() {
        const newAddressForm = document.getElementById('new_address_form');
        if (newAddressForm.style.display === 'none' || newAddressForm.style.display === '') {
            newAddressForm.style.display = 'block';
        } else {
            newAddressForm.style.display = 'none';
        }
    });

    const municipalities = <?php echo json_encode($municipalities); ?>;
    document.getElementById('municipality').addEventListener('change', function () {
        const selectedMunicipality = this.value;
        const barangaySelect = document.getElementById('barangay');

        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        if (municipalities[selectedMunicipality]) {
            municipalities[selectedMunicipality].forEach(function (barangay) {
                const option = document.createElement('option');
                option.value = barangay;
                option.textContent = barangay;
                barangaySelect.appendChild(option);
            });
        }
    });

    // Remove address dynamically
    document.querySelectorAll('.remove-address').forEach(button => {
        button.addEventListener('click', function() {
            const addressId = this.getAttribute('data-address-id');
            const confirmed = confirm('Are you sure you want to remove this address?');
            if (confirmed) {
                fetch('checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `remove_address_id=${addressId}`,
                }).then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Failed to remove address. Please try again.');
                    }
                });
            }
        });
    });
</script>
</body>
<footer>
    <div class="footer-container">
        <div class="footer-section about">
            <h3>About NESRAC</h3>
            <p>We are the Nueva Ecija Swine Raiser Cooperative (NESRAC), committed to providing quality pork products while supporting local farmers.</p>
        </div>
        <div class="footer-section links">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="homepage.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        <div class="footer-section contact">
            <h3>Contact Us</h3>
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Campos, Talavera, Nueva Ecija</li>
                <li><i class="fas fa-phone-alt"></i> (044) 123-4567</li>
                <li><i class="fas fa-envelope"></i> contact@nesrac.com</li>
            </ul>
        </div>
        <div class="footer-section social">
            <h3>Follow Us</h3>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> NESRAC. All rights reserved.</p>
    </div>
    <style>
    footer {
        background-color: #333;
        color: white;
        padding: 10px 0;
    }
    .footer-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        max-width: 1200px;
        margin: 0 auto;
    }
    .footer-section {
        flex-basis: 22%;
        margin: 5px;
    }
    .footer-section h3 {
        margin-bottom: 5px;
        font-size: 15px;
    }
    .footer-section p {
        font-size: 12px;
        line-height: 1.6;
    }
    .footer-section ul {
        list-style: none;
        padding: 0;
    }
    .footer-section ul li {
        margin-bottom: 3px;
    }
    .footer-section ul li i {
        margin-right: 3px;
    }
    .footer-section ul li a {
        color: white;
        text-decoration: none;
    }
    .footer-section ul li a:hover {
        color: #00aaff;
    }
    .footer-section.social a {
        display: inline-block;
        margin-right: 5px;
        color: white;
        font-size: 16px;
        transition: color 0.3s ease;
    }
    .footer-section.social a:hover {
        color: #00aaff;
    }
    .footer-bottom {
        text-align: center;
        margin-top: 5px;
    }
    .footer-bottom p {
        font-size: 11px;
        margin: 0;
    }
</style>
</footer>
</html>
