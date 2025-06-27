<?php
session_start();
// (auth & DB connect...)
$stats = [];
$res = $conn->query("
 SELECT MONTH(due_date) m,
 SUM(is_completed=1) c,
 SUM(is_completed=0) p
 FROM tasks WHERE user_id = {$userId}
 GROUP BY m
");
$labels = $completed = $pending = [];
foreach($res as $r) {
  $labels[] = DateTime::createFromFormat('!m', $r['m'])->format('F');
  $completed[] = (int)$r['c'];
  $pending[] = (int)$r['p'];
}
$res2 = $conn->query("SELECT 
   COUNT(*) total,
   SUM(is_completed=1) done,
   SUM(is_completed=0 AND due_date<NOW()) overdue
 FROM tasks WHERE user_id = {$userId}");
$sum = $res2->fetch_assoc();
?>
<!DOCTYPE html>
<html><head><title>Analytics</title><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head><body>
<h1>Your Analytics</h1>
<p>Total: <?= $sum['total'] ?> · Completed: <?= $sum['done'] ?> · Overdue: <?= $sum['overdue'] ?></p>
<canvas id="barChart"></canvas>
<script>
const ctx = document.getElementById('barChart');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [
      { label: 'Completed', data: <?= json_encode($completed) ?>, backgroundColor: 'green' },
      { label: 'Pending', data: <?= json_encode($pending) ?>, backgroundColor: 'orange' }
    ]
  }
});
</script>
</body></html>
