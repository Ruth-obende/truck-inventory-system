<?php
/**
 * =============================================================================
 * Moal General Suppliers - RecommendationEngine Class
 * =============================================================================
 * Implements the deterministic proximity scoring algorithm specified in:
 * - Section 3.4.2.4: Find My Truck Recommendation Module
 * - Section 3.5.4.6: RecommendationEngine Class
 * - Section 3.7.5:   Truck Recommendation Requirements
 * 
 * Criteria Weighting:
 * - Operational Purpose: 50% (0.50)
 * - Budget Limit:        30% (0.30)
 * - Minimum Payload:     20% (0.20)
 */

declare(strict_types=1);

class RecommendationEngine
{
    /** @var float Weight assigned to operational purpose matching (50%) */
    private float $purposeWeight = 0.50;

    /** @var float Weight assigned to budget limit proximity (30%) */
    private float $budgetWeight = 0.30;

    /** @var float Weight assigned to payload capacity fulfillment (20%) */
    private float $tonnageWeight = 0.20;

    /** @var PDO Database connection */
    private PDO $db;

    /**
     * Constructor
     * 
     * @param PDO $db Active PDO database instance
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Evaluates customer criteria against available truck inventory.
     * Returns exact matches (100% match) if available; otherwise returns
     * up to 3 ranked fallback alternatives with calculated proximity scores.
     *
     * @param string $purpose    Selected operational purpose
     * @param float  $budget     Maximum target budget in NGN
     * @param float  $minPayload Minimum payload capacity in metric tons
     * @return array Array containing 'match_type' ('exact'|'alternative'|'none') and 'trucks'
     */
    public function findMatchingTrucks(string $purpose, float $budget, float $minPayload): array
    {
        // 1. Fetch all available trucks with primary image
        $sql = "
            SELECT t.*, 
                   (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
            FROM trucks t
            WHERE t.availability_status = 'Available'
            ORDER BY t.price ASC
        ";
        $stmt = $this->db->query($sql);
        $allTrucks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($allTrucks)) {
            return [
                'match_type' => 'none',
                'trucks'     => [],
                'total'      => 0
            ];
        }

        // 2. Identify strict exact matches:
        // Purpose matches identical, price <= budget, and tonnage >= minPayload
        $exactMatches = [];
        foreach ($allTrucks as $truck) {
            $truckPrice   = (float)$truck['price'];
            $truckTonnage = (float)$truck['tonnage_capacity'];
            $truckPurpose = trim((string)$truck['purpose_category']);

            $purposeMatch = (strcasecmp($truckPurpose, trim($purpose)) === 0);
            $budgetMatch  = ($budget <= 0 || $truckPrice <= $budget);
            $tonnageMatch = ($minPayload <= 0 || $truckTonnage >= $minPayload);

            if ($purposeMatch && $budgetMatch && $tonnageMatch) {
                $truck['match_score'] = 1.0;
                $truck['match_percentage'] = 100;
                $truck['is_exact'] = true;
                $truck['score_breakdown'] = [
                    'purpose' => 100,
                    'budget'  => 100,
                    'tonnage' => 100
                ];
                $exactMatches[] = $truck;
            }
        }

        if (!empty($exactMatches)) {
            // Sort exact matches by price descending
            usort($exactMatches, fn($a, $b) => (float)$b['price'] <=> (float)$a['price']);
            return [
                'match_type' => 'exact',
                'trucks'     => $exactMatches,
                'total'      => count($exactMatches)
            ];
        }

        // 3. Exact match unavailable -> calculate proximity score and rank alternatives
        $alternatives = $this->rankFallbackAlternatives($allTrucks, $purpose, $budget, $minPayload);

        return [
            'match_type' => !empty($alternatives) ? 'alternative' : 'none',
            'trucks'     => $alternatives,
            'total'      => count($alternatives)
        ];
    }

    /**
     * Calculates the deterministic proximity score (0.00 to 1.00) for a truck.
     * - Operational Purpose: 50% (1.0 if identical, 0.50 if compatible, 0.0 otherwise)
     * - Budget Limit:        30% (1.0 if within budget, proportionally scaled down if over budget)
     * - Minimum Payload:     20% (1.0 if >= requirement, proportionally scaled if under requirement)
     *
     * @param array  $truck      Truck record associative array
     * @param string $purpose    Requested operational purpose
     * @param float  $budget     Requested budget limit
     * @param float  $minPayload Requested minimum tonnage
     * @return array Array containing 'score' (0.0 - 1.0), 'percentage' (0 - 100), and 'breakdown'
     */
    public function calculateProximityScore(array $truck, string $purpose, float $budget, float $minPayload): array
    {
        $truckPurpose = trim((string)$truck['purpose_category']);
        $truckPrice   = (float)$truck['price'];
        $truckTonnage = (float)$truck['tonnage_capacity'];

        // --- 1. Operational Purpose Component (50%) ---
        $purposeScore = 0.0;
        if (strcasecmp($truckPurpose, trim($purpose)) === 0) {
            $purposeScore = 1.0;
        } else {
            // Check domain compatibility matrix between commercial transport categories
            $related = [
                'Construction & Mining'    => ['Heavy Haulage' => 0.50, 'Specialized Transport' => 0.25],
                'Heavy Haulage'            => ['Construction & Mining' => 0.50, 'Distribution & Logistics' => 0.40],
                'Distribution & Logistics' => ['Agriculture & Farming' => 0.50, 'Heavy Haulage' => 0.40],
                'Agriculture & Farming'    => ['Distribution & Logistics' => 0.50, 'Heavy Haulage' => 0.40],
                'Specialized Transport'    => ['Heavy Haulage' => 0.50, 'Construction & Mining' => 0.25]
            ];
            if (isset($related[$purpose][$truckPurpose])) {
                $purposeScore = $related[$purpose][$truckPurpose];
            }
        }
        $purposeContribution = $purposeScore * $this->purposeWeight;

        // --- 2. Budget Limit Component (30%) ---
        $budgetScore = 1.0;
        if ($budget > 0) {
            if ($truckPrice <= $budget) {
                // Completely within budget ceiling
                $budgetScore = 1.0;
            } else {
                // Over budget: score degrades linearly with percentage excess
                // E.g., if price is 20% over budget, score is 0.80
                $excessRatio = ($truckPrice - $budget) / $budget;
                $budgetScore = max(0.0, 1.0 - $excessRatio);
            }
        }
        $budgetContribution = $budgetScore * $this->budgetWeight;

        // --- 3. Minimum Payload Component (20%) ---
        $tonnageScore = 1.0;
        if ($minPayload > 0) {
            if ($truckTonnage >= $minPayload) {
                // Fully satisfies minimum required tonnage
                $tonnageScore = 1.0;
            } else {
                // Below required tonnage: ratio of available to required
                $tonnageScore = max(0.0, $truckTonnage / $minPayload);
            }
        }
        $tonnageContribution = $tonnageScore * $this->tonnageWeight;

        // Total weighted score
        $totalScore = $purposeContribution + $budgetContribution + $tonnageContribution;
        $totalScore = min(1.0, max(0.0, $totalScore));
        $matchPercentage = (int)round($totalScore * 100);

        return [
            'score'      => $totalScore,
            'percentage' => $matchPercentage,
            'breakdown'  => [
                'purpose' => (int)round($purposeScore * 100),
                'budget'  => (int)round($budgetScore * 100),
                'tonnage' => (int)round($tonnageScore * 100)
            ]
        ];
    }

    /**
     * Ranks fallback alternatives when an exact match is unavailable.
     * Evaluates proximity scores across all available trucks and returns
     * up to 3 highest-ranking alternatives (Section 3.7.5).
     *
     * @param array  $trucks     List of available trucks
     * @param string $purpose    Requested operational purpose
     * @param float  $budget     Requested budget limit
     * @param float  $minPayload Requested minimum tonnage
     * @return array Up to 3 ranked trucks with match scores
     */
    public function rankFallbackAlternatives(array $trucks, string $purpose, float $budget, float $minPayload): array
    {
        $scoredTrucks = [];

        foreach ($trucks as $truck) {
            $eval = $this->calculateProximityScore($truck, $purpose, $budget, $minPayload);
            $truck['match_score']      = $eval['score'];
            $truck['match_percentage'] = $eval['percentage'];
            $truck['score_breakdown']  = $eval['breakdown'];
            $truck['is_exact']         = false;
            $scoredTrucks[] = $truck;
        }

        // Sort descending by match score, then secondary by price
        usort($scoredTrucks, function ($a, $b) {
            if ($b['match_score'] <=> $a['match_score']) {
                return $b['match_score'] <=> $a['match_score'];
            }
            return (float)$a['price'] <=> (float)$b['price'];
        });

        // Return up to three (3) suitable alternatives as mandated by Section 3.7.5
        return array_slice($scoredTrucks, 0, 3);
    }
}
