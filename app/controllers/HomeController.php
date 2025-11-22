<?php
// FILE: /app/controllers/HomeController.php

/**
 * HomeController - Handles homepage and landing page
 */
class HomeController extends Controller
{
    /**
     * Show homepage
     */
    public function index()
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $planModel = new PlanModel();
        $plans = $planModel->getActivePlans();

        $this->render('home/index', ['plans' => $plans], 'layout_public');
    }
}
