<?php
// FILE: /app/models/PlanModel.php

/**
 * PlanModel - Manages subscription plans
 *
 * Handles plan CRUD operations and feature management
 */
class PlanModel extends Model
{
    protected $table = 'plans';

    /**
     * Get active plans
     *
     * @return array Plans list
     */
    public function getActivePlans()
    {
        return $this->findAll(['is_active' => 1], 'price_monthly ASC');
    }

    /**
     * Get plan with parsed features
     *
     * @param int $planId Plan ID
     * @return array|null Plan data
     */
    public function getPlanWithFeatures($planId)
    {
        $plan = $this->findById($planId);

        if ($plan && !empty($plan['features'])) {
            $plan['features_array'] = json_decode($plan['features'], true);
        }

        return $plan;
    }
}
