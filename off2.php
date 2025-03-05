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


// Function to fetch a single row
function fetchSingleRow($conn, $query, $paramTypes, ...$params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch multiple rows
function fetchAllRows($conn, $query, $paramTypes, ...$params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function for Insert, Update, and Delete queries
function executeQuery($conn, $query, $paramTypes, ...$params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($paramTypes, ...$params);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}


// Fetch officer's ID
$officer = fetchSingleRow($conn, "SELECT offID FROM officer_login WHERE username = ?", "s", $username);
$offID = $officer['offID'] ?? null;

// Fetch officer's details
$officerDetails = fetchSingleRow($conn, "SELECT Fname, Lname, phone_no, email, age, sex FROM officers WHERE offID = ?", "i", $offID);


//ALL RECORDS

// Fetch all Field Officers 
$field_officers = fetchAllRows($conn, "
    SELECT o.offID, o.Fname, o.Lname, o.phone_no, o.email, o.age, 
           COUNT(DISTINCT f.requestID) AS request_count,
           COUNT(DISTINCT fa.farmerID) AS farmer_count
    FROM officers o
    LEFT JOIN fertilizer_requests f ON o.offID = f.registeredBy
    LEFT JOIN farmers fa ON o.offID = fa.registeredBy
    WHERE o.supervisorID = ?
    GROUP BY o.offID, o.Fname, o.Lname, o.phone_no, o.email, o.age
", "i", $offID);

// Fetch all fertilizer requests
$requests = fetchAllRows($conn, "
    SELECT f.requestID, f.registeredBy, f.farmerID, f.landID, f.quantityRequested, 
           f.requestDate, f.status
    FROM fertilizer_requests f
    JOIN officers o ON f.registeredBy = o.offID
    WHERE o.supervisorID = ?
    ORDER BY f.requestDate DESC
", "i", $offID);


//SEARCH BY ID

//Field Officer by ID
if (isset($_POST['searcho1btn'])) {
    $search = intval($_POST['search']); // Ensure ID is an integer

    // Fetch the Field Officer details
    $searchrow = fetchSingleRow($conn, "
        SELECT offID, Fname, Lname, phone_no, email 
        FROM officers 
        WHERE supervisorID = ? AND offID = ?", 
        "ii", $offID, $search
    );

    // If a Field Officer is found, fetch their fertilizer requests
    if ($searchrow) 
    {
        $searchoffreq = fetchAllRows($conn,"SELECT requestID, registeredBy, farmerID, landID, quantityRequested, requestDate, status
                  FROM fertilizer_requests
                  WHERE registeredBy = ? 
                  ORDER BY requestDate DESC", "i", $search);
    }
}


//Request by ID
if (isset($_POST['searchreqbtn'])) {
    $search = intval($_POST['search']); // Ensure ID is an integer

    // Ensure request is made by a Field Officer under this Junior Officer
    $searchreq = fetchSingleRow(
        $conn,
        "SELECT fr.requestID, fr.landID, fr.farmerID, fr.quantityRequested, fr.requestDate, fr.registeredBy, fr.status
        FROM fertilizer_requests fr
        JOIN officers o ON fr.registeredBy = o.offID
        WHERE fr.requestID = ? AND o.supervisorID = ? 
        ORDER BY fr.requestDate DESC",
        "ii",
        $search,
        $offID
    );
}


// Approve or reject fertilizer requests
if (isset($_POST['approvebtn']) || isset($_POST['rejectbtn'])) {
    $requestID = $_POST['requestID'];
    $status = isset($_POST['approvebtn']) ? 'Approved' : 'Rejected';

    $result = executeQuery($conn, "UPDATE fertilizer_requests SET status = ?, reviewedBy=? WHERE requestID = ?", "sii", $status, $offID,$requestID);
    if ($result) {
        header("Location: Off2.php");
        exit();
    }
}

$conn->close();
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
    <h1>Junior Officer Dashboard</h1>
    <h2>Welcome, <?php echo $officerDetails['Fname'] . " " . $officerDetails['Lname']; ?></h2>
    <hr>
    <h3>Profile</h3>
    <p>First Name: <?php echo $officerDetails['Fname']; ?></p>
    <p>Last Name: <?php echo $officerDetails['Lname']; ?></p>
    <p>Phone Number: <?php echo $officerDetails['phone_no']; ?></p>
    <p>Email: <?php echo $officerDetails['email']; ?></p>
    <p>Age: <?php echo $officerDetails['age']; ?></p>
    
    <hr>

    <h3>Field Officers</h3>

    <!--search by ID-->
    <h4> search by Id </h4>
    <form method="post">
        <input type="text" name="search" placeholder="Search by ID">
        <button type="submit" name="searcho1btn">Search</button>
    </form>
    <?php if (!empty($searchrow)): ?>
        <!--field off details-->
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Phone Number</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $search; ?></td>
                    <td><?php echo $searchrow['Fname']; ?></td>
                    <td><?php echo $searchrow ['Lname']; ?></td>
                    <td><?php echo $searchrow['phone_no']; ?></td>
                    <td><?php echo $searchrow['email']; ?></td>
                </tr>
            </tbody>
        </table>
        <h4> Requests </h4>
        <?php if(!empty($searchoffreq)): ?>
        <!--requests under the field off -->
            <table border="1">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Request ID</th>
                        <th>Land ID</th>
                        <th>Farmer ID</th>
                        <th>Quantity Requested</th>
                        <th>Request Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php $index = 1; // Initialize counter ?>
                <?php foreach ($searchoffreq as $req): ?>
                    <tr>
                        <td><?php echo $index++; ?></td>
                        <td><?php echo $req['requestID']; ?></td>
                        <td><?php echo $req['landID']; ?></td>
                        <td><?php echo $req['farmerID']; ?></td>
                        <td><?php echo $req['quantityRequested']; ?></td>
                        <td><?php echo $req['requestDate']; ?></td>
                        <td><?php echo $req['status']; ?></td>
                    </tr>
                </tbody>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No requests found</p>
        <?php endif; ?>
    <?php else: ?>
        <p>No Field Officer found</p>
    <?php endif; ?>

    <h4> All Field Officers </h4>
    <table border="1">
        <thead>
            <tr>
                <th>#</th>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Phone Number</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 1; // Initialize counter ?>
            <?php foreach ($field_officers as $field_officer): ?>
                <tr>
                    <td><?php echo $index++; ?></td>
                    <td><?php echo $field_officer['offID']; ?></td>
                    <td><?php echo $field_officer['Fname']; ?></td>
                    <td><?php echo $field_officer['Lname']; ?></td>
                    <td><?php echo $field_officer['phone_no']; ?></td>
                    <td><?php echo $field_officer['email']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h3>Requests</h3>
    <h4> search by id </h4>
    <form method="post">
        <input type="text" name="search" placeholder="Search by ID">
        <button type="submit" name="searchreqbtn">Search</button>
    </form>
    <?php if (!empty($searchreq)): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Land ID</th>
                    <th>Farmer ID</th>
                    <th>Quantity Requested</th>
                    <th>Request Date</th>
                    <th>RegisterdBy</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $search; ?></td>
                    <td><?php echo $searchreq['landID']; ?></td>
                    <td><?php echo $searchreq['farmerID']; ?></td>
                    <td><?php echo $searchreq['quantityRequested']; ?></td>
                    <td><?php echo $searchreq['requestDate']; ?></td>
                    <td><?php echo $searchreq['registeredBy']; ?></td>
                    <td><?php echo $searchreq['status']; ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <p>No requests found</p>
    <?php endif; ?>

    <h3>All Fertilizer Requests</h3>
    <table border="1">
        <thead>
            <tr>
                <th>#</th>
                <th>Request ID</th>
                <th>Land ID</th>
                <th>Farmer ID</th>
                <th>Quantity Requested</th>
                <th>Request Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 1; // Initialize counter ?>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo $index++; ?></td>
                    <td><?php echo $request['requestID']; ?></td>
                    <td><?php echo $request['landID']; ?></td>
                    <td><?php echo $request['farmerID']; ?></td>
                    <td><?php echo $request['quantityRequested']; ?></td>
                    <td><?php echo $request['requestDate']; ?></td>
                    <td><?php echo $request['status']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h3>Approval Requests</h3>
    <table border="1">
        <thead>
            <tr>
                <th>#</th>
                <th>Request ID</th>
                <th>Land ID</th>
                <th>Farmer ID</th>
                <th>Quantity Requested</th>
                <th>Request Date</th>
                <th>Status</th>
                <th> Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 1; // Initialize counter ?>
            <?php foreach (array_reverse($requests) as $request): ?>
                <?php if ($request['status'] === 'Pending'): ?>
                    <tr>
                        <td><?php echo $index++; ?></td>
                        <td><?php echo $request['requestID']; ?></td>
                        <td><?php echo $request['landID']; ?></td>
                        <td><?php echo $request['farmerID']; ?></td>
                        <td><?php echo $request['quantityRequested']; ?></td>
                        <td><?php echo $request['requestDate']; ?></td>
                        <td><?php echo $request['status']; ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="requestID" value="<?php echo $request['requestID']; ?>">
                                <button type="submit" name="approvebtn">Approve</button>
                                <button type="submit" name="rejectbtn">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h3>analytics</h3>
    <h4>Requests by officers</h4>
    <table border="1">
        <thead>
            <tr>
                <th>#</th>
                <th>Officer ID</th>
                <th>no. of requests</th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 1; // Initialize counter ?>
            <?php foreach ($field_officers as $field_officer): ?>
                <tr>
                    <td><?php echo $index++; ?></td>
                    <td><?php echo $field_officer['offID']; ?></td>
                    <td><?php echo $field_officer['request_count']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4>count of farmers</h4>
    <table border="1">
    <thead>
            <tr>
                <th>#</th>
                <th>Officer ID</th>
                <th>no. of farmers</th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 1; // Initialize counter ?>
            <?php foreach ($field_officers as $field_officer): ?>
                <tr>
                    <td><?php echo $index++; ?></td>
                    <td><?php echo $field_officer['offID']; ?></td>
                    <td><?php echo $field_officer['farmer_count']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <a href="OfficerLogout.php">Logout</a>

</body>
</html>
