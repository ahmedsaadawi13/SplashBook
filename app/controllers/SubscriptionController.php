<?php
// FILE: /app/controllers/SubscriptionController.php

class SubscriptionController extends Controller
{
    private $subscriptionModel;
    private $planModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['tenant_admin']);
        $this->subscriptionModel = new SubscriptionModel();
        $this->planModel = new PlanModel();
    }

    public function index()
    {
        $subscription = $this->subscriptionModel->getActiveSubscription($this->getTenantId());
        $history = $this->subscriptionModel->getSubscriptionHistory($this->getTenantId());

        $this->render('subscription/index', compact('subscription', 'history'));
    }

    public function plans()
    {
        $plans = $this->planModel->getActivePlans();
        $currentSubscription = $this->subscriptionModel->getActiveSubscription($this->getTenantId());

        $this->render('subscription/plans', compact('plans', 'currentSubscription'));
    }

    public function changePlan()
    {
        $this->requireCsrf();
        $planId = $this->request->post('plan_id');

        $plan = $this->planModel->findById($planId);
        if (!$plan) {
            $this->setFlash('error', 'Invalid plan');
            $this->redirect('/subscription/plans');
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Create new subscription
            $this->subscriptionModel->insert([
                'tenant_id' => $this->getTenantId(),
                'plan_id' => $planId,
                'status' => 'active',
                'current_period_start' => date('Y-m-d H:i:s'),
                'current_period_end' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ]);

            $db->commit();

            $this->setFlash('success', 'Plan changed successfully');
            $this->redirect('/subscription');

        } catch (Exception $e) {
            $db->rollBack();
            error_log('Plan change failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to change plan');
            $this->redirect('/subscription/plans');
        }
    }
}
