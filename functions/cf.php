<?php
function getRatings($pdo) {
  $stmt = $pdo->query("SELECT user_id, product_id, rating FROM ratings");
  $ratings = [];
  while ($row = $stmt->fetch()) {
    $ratings[$row['user_id']][$row['product_id']] = $row['rating'];
  }
  return $ratings;
}

function pearson($ratings, $u1, $u2) {
  // Validasi: pastikan kedua user memiliki rating
  if (!isset($ratings[$u1]) || !isset($ratings[$u2]) || 
      !is_array($ratings[$u1]) || !is_array($ratings[$u2])) {
    return 0;
  }
  
  $common = array_intersect_key($ratings[$u1], $ratings[$u2]);
  $n = count($common);
  if ($n == 0) return 0;

  $sum1 = $sum2 = $sum1Sq = $sum2Sq = $pSum = 0;
  foreach ($common as $item => $_) {
    $r1 = $ratings[$u1][$item];
    $r2 = $ratings[$u2][$item];
    $sum1 += $r1;
    $sum2 += $r2;
    $sum1Sq += pow($r1, 2);
    $sum2Sq += pow($r2, 2);
    $pSum += $r1 * $r2;
  }

  $num = $pSum - ($sum1 * $sum2 / $n);
  $den = sqrt(($sum1Sq - pow($sum1, 2) / $n) * ($sum2Sq - pow($sum2, 2) / $n));
  return $den == 0 ? 0 : $num / $den;
}

function getRecommendations($pdo, $targetUser) {
  $ratings = getRatings($pdo);
  
  // Validasi: pastikan target user memiliki data rating
  if (!isset($ratings[$targetUser]) || !is_array($ratings[$targetUser]) || empty($ratings[$targetUser])) {
    return []; // Return empty array jika user belum pernah rating
  }
  
  $totals = [];
  $simSums = [];

  foreach ($ratings as $otherUser => $otherRatings) {
    if ($otherUser == $targetUser) continue;
    
    // Skip jika other user tidak memiliki rating yang valid
    if (!is_array($otherRatings) || empty($otherRatings)) continue;
    
    $sim = pearson($ratings, $targetUser, $otherUser);
    if ($sim <= 0) continue;

    foreach ($otherRatings as $item => $rating) {
      if (!isset($ratings[$targetUser][$item])) {
        $totals[$item] = ($totals[$item] ?? 0) + $rating * $sim;
        $simSums[$item] = ($simSums[$item] ?? 0) + $sim;
      }
    }
  }

  $rankings = [];
  foreach ($totals as $item => $total) {
    $rankings[$item] = $total / $simSums[$item];
  }
  arsort($rankings);
  return array_keys($rankings);
}
?>
