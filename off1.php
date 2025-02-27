<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: OfficerLogin.php");
    exit();
}

// Include the database connection
include 'connection.php';

// Get the logged-in officer's username
$username = $_SESSION['username'];

// Fetch officer's login details
$stmt = $conn->prepare("SELECT offID FROM officer_login WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($offID);
$stmt->fetch();
$stmt->close();

// Fetch officer's personal details
$stmt = $conn->prepare("SELECT Fname, Lname, phone_no, email, age, sex FROM officers WHERE offID = ?");
$stmt->bind_param("i", $offID);
$stmt->execute();
$stmt->bind_result($oFname, $oLname, $ophone_no, $oemail, $oage, $osex);
$stmt->fetch();
$stmt->close();

$msg1 = $msg2 = $msg3 = "";
$farmerID = $firstName = $lastName = $age = $telNo = $sex = "";

// Step 1: Search FarmerID
if (isset($_POST['btnSearchID'])) {
    $farmerID = $_POST['txtFarmerID'];

    $query = "SELECT * FROM farmers WHERE FarmerID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $farmerID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($record = $result->fetch_assoc()) {
        $firstName = $record['FName'];
        $lastName = $record['LName'];
        $age = $record['age'];
        $sex = $record['sex'];
        $telNo = $record['phone_no'];
        $addy = $record['addy'];
    } else {
        $msg1 = "No such FarmerID exists.";
    }
    $stmt->close();
}

// Step 2: Update Farmer Data
if (isset($_POST['btn_save'])) {
    $farmerID = $_POST['txtFarmerID'];
    $firstName = $_POST['txtFirstName'];
    $lastName = $_POST['txtLastName'];
    $age = $_POST['txtage'];
    $sex = $_POST['txtsex'];
    $telNo = $_POST['txtTelNo'];
    $addy = $_POST['txtaddy'];

    $query = "UPDATE farmers SET fname = ?, lname = ?, age = ?, sex = ?, phone_no = ?, addy=? WHERE FarmerID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssisssi", $firstName, $lastName, $age, $sex, $telNo, $addy, $farmerID);
    $result = $stmt->execute();

    $msg2 = $result ? "Record updated successfully." : "Failed updating the record: " . $stmt->error;
    $stmt->close();
}

// Step 3: Add New Farmer
if (isset($_POST['btn_add'])) {
    $firstName = $_POST['txtNewFirstName'];
    $lastName = $_POST['txtNewLastName'];
    $age = $_POST['txtNewAge'];
    $sex = $_POST['txtNewSex'];
    $telNo = $_POST['txtNewTelNo'];
    $addy = $_POST['txtNewAddy'];

    $query = "INSERT INTO farmers (fname, lname, age, sex, phone_no, addy, registeredBy) VALUES (?, ?, ?, ?, ?, ?,?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssisssi", $firstName, $lastName, $age, $sex, $telNo, $addy, $offID);
    $result = $stmt->execute();

    $msg3 = $result ? "New farmer added successfully." : "Failed to add farmer: " . $stmt->error;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer's Dashboard</title>
    <link rel="stylesheet" href="farmer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div id="officer-dashboard" class="officer-tab-container">
        <h1>Field Officer's Dashboard</h1>
        <h2>Welcome, <?php echo htmlspecialchars($oFname) . " " . htmlspecialchars($oLname); ?>!</h2>

        <div class="card-container">
            <!-- Profile Card -->
            <div class="card">
                <i class="fas fa-user icon"></i>
                <h3>Officer's Profile</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($oFname) . " " . htmlspecialchars($oLname); ?></p>
                <p><strong>Phone No:</strong> <?php echo htmlspecialchars($ophone_no); ?></p>
                <p><strong>Age:</strong> <?php echo htmlspecialchars($oage); ?></p>
                <p><strong>Sex:</strong> <?php echo htmlspecialchars($osex); ?></p>
            </div>
        </div>

        <a href="OfficerLogin.php" class="logout-btn">Logout</a> <!-- Logout Button -->

        <h2>Search Farmer by ID</h2>
        <form method="post">
            <label for="txtFarmerID">Enter Farmer ID:</label>
            <input type="text" id="txtFarmerID" name="txtFarmerID" required>
            <button type="submit" name="btnSearchID">Search</button>
        </form>
        <?php if (!empty($msg1)) echo "<p>$msg1</p>"; ?>

        <?php if (!empty($firstName)) : ?>
        <h2>Farmer Details</h2>
        <p><strong>Farmer ID:</strong> <?php echo htmlspecialchars($farmerID); ?></p>
        <p><strong>First Name:</strong> <?php echo htmlspecialchars($firstName); ?></p>
        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($lastName); ?></p>
        <p><strong>Age:</strong> <?php echo htmlspecialchars($age); ?></p>
        <p><strong>sex:</strong> <?php echo htmlspecialchars($sex); ?></p>
        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($telNo); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($addy); ?></p>

        <h2>Update Farmer Details</h2>
        <form method="post">
            <input type="hidden" name="txtFarmerID" value="<?php echo htmlspecialchars($farmerID); ?>">
            <label for="txtFirstName">First Name:</label>
            <input type="text" id="txtFirstName" name="txtFirstName" value="<?php echo htmlspecialchars($firstName); ?>" required>
            <br>

            <label for="txtLastName">Last Name:</label>
            <input type="text" id="txtLastName" name="txtLastName" value="<?php echo htmlspecialchars($lastName); ?>" required>
            <br>

            <label for="txtage">Age:</label>
            <input type="number" id="txtage" name="txtage" value="<?php echo htmlspecialchars($age); ?>" required>
            <br>

            <label for="txtsex">Sex:</label>
            <select id="txtsex" name="txtsex" required>
                <option value="Male" <?php echo ($sex == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($sex == 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo ($sex == 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
            <br>

            <label for="txtTelNo">Phone Number:</label>
            <input type="tel" id="txtTelNo" name="txtTelNo" value="<?php echo htmlspecialchars($telNo); ?>" required>
            <br>

            <label for="txtaddy">Address:</label>
            <input type="text" id="txtaddy" name="txtaddy" value="<?php echo htmlspecialchars($addy); ?>" required>
            <br>

            <button type="submit" name="btn_save">save</button>
        </form>
        
        <?php endif; ?>
        <h2>Add New Farmer</h2>
        <form method="post">
            <label for="txtNewFirstName">First Name:</label>
            <input type="text" name="txtNewFirstName" required placeholder="First Name">
            <br>
            <label for="txtNewLastName">Last Name:</label>
            <input type="text" name="txtNewLastName" required placeholder="Last Name">
            <br>
            <label for="txtNewSex">sex:</label>
            <select name="txtNewSex" required>                  
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
            <br>
            <label for="txtNewAge">Age:</label>
            <input type="number" name="txtNewAge" required placeholder="Age">
            <br>
            <label for="txtNewTelNo">Phone Number:</label>
            <input type="tel" name="txtNewTelNo" required placeholder="Phone Number">
            <br>
            <label for="txtNewAddy">Address:</label>
            <input type="text" name="txtNewAddy" required placeholder="Address">
            <br>
            <button type="submit" name="btn_add">Add Farmer</button>
        </form>
        <?php if (!empty($msg3)) echo "<p>$msg3</p>"; ?>
    </div>
</body>
</html>
