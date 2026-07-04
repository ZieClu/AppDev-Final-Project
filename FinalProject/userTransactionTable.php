<?php 
session_start();

if(!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}

include 'connection.php';

$user_id = $_SESSION['user']['user_id'];

$sql = "SELECT b.title AS book_title, u.username AS buyer_name, 
        u.user_id AS buyer_id, b.price AS book_price, p.purchase_date
        FROM purchases p 
        JOIN books b ON p.book_id = b.book_id
        JOIN users u ON p.buyer_id = u.user_id
        WHERE p.seller_id = ?
        ORDER BY p.purchase_date DESC";

$sold_stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($sold_stmt, "i", $user_id);
mysqli_stmt_execute($sold_stmt);
$sold_result = mysqli_stmt_get_result($sold_stmt);
$num_rows = mysqli_num_rows($sold_result);
?>

<h2>My Sales History</h2>

<table>
    <tr>
        <th>Book Title</th>
        <th>Buyer ID</th>
        <th>Buyer</th>
        <th>Price Paid</th>
        <th>Purchase Date</th>
    </tr>
    <?php if ($num_rows > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($sold_result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_id']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_name']); ?></td>
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

<?php mysqli_stmt_close($sold_stmt); ?>

<a href="home.php"> Back </a>
