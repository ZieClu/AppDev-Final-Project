<?php 
session_start();

if(!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

include 'connection.php';

$user_id = $_SESSION['user']['user_id'];

$sql = "SELECT b.title AS book_title, u.username AS seller_name, 
        u.user_id AS seller_id, b.price AS book_price, p.purchase_date
        FROM purchases p 
        JOIN books b ON p.book_id = b.book_id
        JOIN users u ON b.seller_id = u.user_id
        WHERE p.buyer_id = ?
        ORDER BY p.purchase_date DESC";

$purchased_stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($purchased_stmt, "i", $user_id);
mysqli_stmt_execute($purchased_stmt);
$purchased_result = mysqli_stmt_get_result($purchased_stmt);
$num_rows = mysqli_num_rows($purchased_result);
?>

<h2>My Purchase History</h2>

<table>
    <tr>
        <th>Book Title</th>
        <th>Seller ID</th>
        <th>Seller</th>
        <th>Price</th>
        <th>Purchase Date</th>
    </tr>
    <?php if ($num_rows > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($purchased_result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                <td><?php echo htmlspecialchars($row['seller_id']); ?></td>
                <td><?php echo htmlspecialchars($row['seller_name']); ?></td>
                <td>Php <?php echo number_format($row['book_price'], 2); ?></td>
                <td><?php echo date('M d, Y', strtotime($row['purchase_date'])); ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td>You haven't sold any books yet.</td>
        </tr>
    <?php endif; ?>
</table>

<?php mysqli_stmt_close($purchased_stmt); ?>

<a href="home.php"> Back </a>

