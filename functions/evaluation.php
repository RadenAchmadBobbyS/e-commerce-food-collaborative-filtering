<?php
/**
 * Evaluation Functions for Collaborative Filtering System
 * Contains MAE, RMSE, and other evaluation metrics
 */

require_once 'cf.php';

/**
 * Calculate Mean Absolute Error (MAE) for recommendation system
 * @param PDO $pdo Database connection
 * @param float $testRatio Ratio of data to use for testing (0.2 = 20%)
 * @return array Contains MAE value and evaluation details
 */
function calculateMAE($pdo, $testRatio = 0.2) {
    try {
        // Get all ratings from database
        $stmt = $pdo->query("SELECT user_id, product_id, rating FROM ratings ORDER BY RAND()");
        $allRatings = $stmt->fetchAll();
        
        if (count($allRatings) < 10) {
            return [
                'mae' => null,
                'error' => 'Insufficient data for evaluation (minimum 10 ratings required)',
                'total_ratings' => count($allRatings)
            ];
        }
        
        // Split data into training and testing sets
        $totalRatings = count($allRatings);
        $testSize = (int)($totalRatings * $testRatio);
        $testData = array_slice($allRatings, 0, $testSize);
        $trainData = array_slice($allRatings, $testSize);
        
        // Create training set ratings structure
        $trainRatings = [];
        foreach ($trainData as $rating) {
            $trainRatings[$rating['user_id']][$rating['product_id']] = $rating['rating'];
        }
        
        $predictions = [];
        $actualRatings = [];
        $evaluationDetails = [];
        
        // Predict ratings for test set
        foreach ($testData as $testRating) {
            $userId = $testRating['user_id'];
            $productId = $testRating['product_id'];
            $actualRating = $testRating['rating'];
            
            // Skip if user has no training data
            if (!isset($trainRatings[$userId]) || empty($trainRatings[$userId])) {
                continue;
            }
            
            // Calculate predicted rating using collaborative filtering
            $predictedRating = predictRating($trainRatings, $userId, $productId);
            
            if ($predictedRating !== null) {
                $predictions[] = $predictedRating;
                $actualRatings[] = $actualRating;
                
                $evaluationDetails[] = [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'actual' => $actualRating,
                    'predicted' => $predictedRating,
                    'error' => abs($actualRating - $predictedRating)
                ];
            }
        }
        
        if (empty($predictions)) {
            return [
                'mae' => null,
                'error' => 'No predictions could be made with the current algorithm',
                'total_test_items' => count($testData),
                'total_predictions' => 0
            ];
        }
        
        // Calculate MAE
        $absoluteErrors = [];
        for ($i = 0; $i < count($predictions); $i++) {
            $absoluteErrors[] = abs($actualRatings[$i] - $predictions[$i]);
        }
        
        $mae = array_sum($absoluteErrors) / count($absoluteErrors);
        
        return [
            'mae' => round($mae, 4),
            'total_ratings' => $totalRatings,
            'train_size' => count($trainData),
            'test_size' => count($testData),
            'successful_predictions' => count($predictions),
            'prediction_coverage' => round((count($predictions) / count($testData)) * 100, 2),
            'evaluation_details' => array_slice($evaluationDetails, 0, 10), // Show first 10 examples
            'average_error' => round(array_sum($absoluteErrors) / count($absoluteErrors), 4),
            'min_error' => round(min($absoluteErrors), 4),
            'max_error' => round(max($absoluteErrors), 4),
            'error' => null
        ];
        
    } catch (Exception $e) {
        return [
            'mae' => null,
            'error' => 'Error calculating MAE: ' . $e->getMessage()
        ];
    }
}

/**
 * Predict rating for a user-item pair using collaborative filtering
 * @param array $ratings Training ratings data
 * @param int $userId User ID
 * @param int $productId Product ID
 * @return float|null Predicted rating or null if can't predict
 */
function predictRating($ratings, $userId, $productId) {
    if (!isset($ratings[$userId])) {
        return null;
    }
    
    $numerator = 0;
    $denominator = 0;
    
    // Find similar users who rated this product
    foreach ($ratings as $otherUserId => $otherRatings) {
        if ($otherUserId == $userId || !isset($otherRatings[$productId])) {
            continue;
        }
        
        // Calculate similarity between users
        $similarity = pearson($ratings, $userId, $otherUserId);
        
        if ($similarity > 0) {
            $numerator += $similarity * $otherRatings[$productId];
            $denominator += abs($similarity);
        }
    }
    
    if ($denominator == 0) {
        // Fallback: return average rating of the user
        $userRatings = array_values($ratings[$userId]);
        return array_sum($userRatings) / count($userRatings);
    }
    
    $predictedRating = $numerator / $denominator;
    
    // Ensure rating is within valid range (1-5)
    return max(1, min(5, $predictedRating));
}

/**
 * Calculate RMSE (Root Mean Square Error)
 * @param PDO $pdo Database connection
 * @param float $testRatio Ratio of data to use for testing
 * @return array Contains RMSE value and evaluation details
 */
function calculateRMSE($pdo, $testRatio = 0.2) {
    $maeResult = calculateMAE($pdo, $testRatio);
    
    if ($maeResult['mae'] === null) {
        return $maeResult; // Return error from MAE calculation
    }
    
    try {
        // Get the evaluation details from MAE calculation
        $evaluationDetails = $maeResult['evaluation_details'];
        
        // Calculate squared errors
        $squaredErrors = [];
        foreach ($evaluationDetails as $detail) {
            $squaredErrors[] = pow($detail['error'], 2);
        }
        
        $mse = array_sum($squaredErrors) / count($squaredErrors);
        $rmse = sqrt($mse);
        
        $result = $maeResult;
        $result['rmse'] = round($rmse, 4);
        $result['mse'] = round($mse, 4);
        
        return $result;
        
    } catch (Exception $e) {
        return [
            'rmse' => null,
            'error' => 'Error calculating RMSE: ' . $e->getMessage()
        ];
    }
}

/**
 * Calculate Precision and Recall for top-N recommendations
 * @param PDO $pdo Database connection
 * @param int $topN Number of top recommendations to evaluate
 * @param float $relevanceThreshold Minimum rating to consider as relevant
 * @return array Contains precision, recall, and F1-score
 */
function calculatePrecisionRecall($pdo, $topN = 10, $relevanceThreshold = 4.0) {
    try {
        // Get users who have sufficient ratings
        $stmt = $pdo->query("
            SELECT user_id, COUNT(*) as rating_count 
            FROM ratings 
            GROUP BY user_id 
            HAVING rating_count >= 5
            LIMIT 20
        ");
        $users = $stmt->fetchAll();
        
        if (empty($users)) {
            return [
                'precision' => null,
                'recall' => null,
                'f1_score' => null,
                'error' => 'No users with sufficient ratings found'
            ];
        }
        
        $totalPrecision = 0;
        $totalRecall = 0;
        $validUsers = 0;
        
        foreach ($users as $user) {
            $userId = $user['user_id'];
            
            // Get user's actual high-rated items
            $stmt = $pdo->prepare("SELECT product_id FROM ratings WHERE user_id = ? AND rating >= ?");
            $stmt->execute([$userId, $relevanceThreshold]);
            $relevantItems = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($relevantItems)) {
                continue;
            }
            
            // Get recommendations for this user
            $recommendations = getRecommendations($pdo, $userId);
            $topRecommendations = array_slice($recommendations, 0, $topN);
            
            if (empty($topRecommendations)) {
                continue;
            }
            
            // Calculate precision and recall
            $relevantRecommended = array_intersect($topRecommendations, $relevantItems);
            
            $precision = count($relevantRecommended) / count($topRecommendations);
            $recall = count($relevantRecommended) / count($relevantItems);
            
            $totalPrecision += $precision;
            $totalRecall += $recall;
            $validUsers++;
        }
        
        if ($validUsers == 0) {
            return [
                'precision' => null,
                'recall' => null,
                'f1_score' => null,
                'error' => 'No valid users for precision/recall calculation'
            ];
        }
        
        $avgPrecision = $totalPrecision / $validUsers;
        $avgRecall = $totalRecall / $validUsers;
        $f1Score = ($avgPrecision + $avgRecall) > 0 ? 
            (2 * $avgPrecision * $avgRecall) / ($avgPrecision + $avgRecall) : 0;
        
        return [
            'precision' => round($avgPrecision, 4),
            'recall' => round($avgRecall, 4),
            'f1_score' => round($f1Score, 4),
            'evaluated_users' => $validUsers,
            'top_n' => $topN,
            'relevance_threshold' => $relevanceThreshold,
            'error' => null
        ];
        
    } catch (Exception $e) {
        return [
            'precision' => null,
            'recall' => null,
            'f1_score' => null,
            'error' => 'Error calculating precision/recall: ' . $e->getMessage()
        ];
    }
}

/**
 * Comprehensive evaluation of the recommendation system
 * @param PDO $pdo Database connection
 * @return array Contains all evaluation metrics
 */
function comprehensiveEvaluation($pdo) {
    $mae = calculateMAE($pdo);
    $rmse = calculateRMSE($pdo);
    $precisionRecall = calculatePrecisionRecall($pdo);
    
    return [
        'mae_evaluation' => $mae,
        'rmse_evaluation' => $rmse,
        'precision_recall_evaluation' => $precisionRecall,
        'evaluation_timestamp' => date('Y-m-d H:i:s')
    ];
}
?>
