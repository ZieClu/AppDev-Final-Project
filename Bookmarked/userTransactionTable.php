<?php
session_start();
require 'autologin.php';

if (!isset($_SESSION['user']))
{
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

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Sales History · BookMarked</title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts: Fraunces for display, Inter for body/UI — same pairing as the rest of the site -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="bookmarked-styleLoader.css">

<style>
  /* --- Tracking table pages (purchase/sales history) — extends
       bookmarked-style.css tokens, same pattern as inventory.php --- */

  .pt-content {
    position: relative;
    z-index: 1;
    max-width: 1000px;
    margin: 0 auto;
    padding: 2.75rem 2.5rem 4rem;
  }

  .pt-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    padding-bottom: 1.1rem;
    border-bottom: 3px solid var(--teal-900);
    margin-bottom: 2rem;
  }

  .pt-title {
    font-family: var(--font-display);
    font-size: 2.2rem;
    color: var(--teal-900);
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin: 0;
  }

  .pt-title .spark { color: var(--teal-800); }

  .pt-back-link {
    color: var(--teal-900);
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 3px;
  }
  .pt-back-link:hover { color: var(--teal-800); }

  .pt-table-wrap {
    background: var(--cream-50);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 20px 40px -18px #2b1d1259;
  }

  .pt-table {
    width: 100%;
    border-collapse: collapse;
  }

  .pt-table th {
    background: var(--teal-900);
    color: var(--cream-100);
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 0.98rem;
    text-align: left;
    padding: 0.9rem 1.25rem;
  }

  .pt-table td {
    padding: 0.85rem 1.25rem;
    color: var(--ink);
    font-size: 0.95rem;
    border-top: 1px solid #2b1d121a;
  }

  .pt-table tbody tr:nth-child(even) {
    background: var(--sage-100);
  }

  .pt-empty-row td {
    text-align: center;
    color: var(--ink-soft);
    font-style: italic;
    padding: 2rem 1.25rem;
  }

  @media (max-width: 700px) {
    .pt-content { padding: 2rem 1.25rem 3rem; }
    .pt-title { font-size: 1.7rem; }
    .pt-table-wrap { overflow-x: auto; }
  }
</style>
</head>
<body>

<!-- HEADER (shared across the site) -->
<header class="bm-header">
  <a href="homeFront.php" class="bm-wordmark" id="homeLogoLink">🕮 BookMarked<span class="dot">.</span></a>
</header>

<main class="pt-content">

  <div class="pt-header-row">
    <h1 class="pt-title"><span class="spark">&#10022;</span> My Sales History</h1>
    <a href="profile.php" class="pt-back-link">&larr; Back to Account</a>
  </div>

  <div class="pt-table-wrap">
    <table class="pt-table">
      <thead>
        <tr>
          <th>Book Title</th>
          <th>Buyer ID</th>
          <th>Buyer</th>
          <th>Price Paid</th>
          <th>Purchase Date</th>
        </tr>
      </thead>
      <tbody>
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
          <tr class="pt-empty-row">
            <td colspan="5">You haven&rsquo;t sold any books yet.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</main>

<?php mysqli_stmt_close($sold_stmt); ?>
</body>
</html>