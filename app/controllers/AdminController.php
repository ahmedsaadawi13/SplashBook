<?php
// FILE: /app/controllers/AdminController.php

class AdminController extends Controller
{
    private $tenantModel;
    private $planModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();

        if (!$this->isPlatformAdmin()) {
            $this->setFlash('error', 'Access denied');
            $this->redirect('/dashboard');
        }

        $this->tenantModel = new TenantModel();
        $this->planModel = new PlanModel();
    }

    public function tenants()
    {
        $tenants = $this->tenantModel->findAll([], 'business_name ASC');
        $this->render('admin/tenants', ['tenants' => $tenants]);
    }

    public function tenantDetail($id)
    {
        $tenant = $this->tenantModel->getTenantWithSubscription($id);
        if (!$tenant) {
            $this->setFlash('error', 'Tenant not found');
            $this->redirect('/admin/tenants');
        }

        $this->render('admin/tenant_detail', ['tenant' => $tenant]);
    }

    public function suspendTenant($id)
    {
        $this->requireCsrf();

        try {
            $this->tenantModel->update($id, ['status' => 'suspended']);
            $this->setFlash('success', 'Tenant suspended');
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to suspend tenant');
        }

        $this->redirect('/admin/tenants/' . $id);
    }

    public function activateTenant($id)
    {
        $this->requireCsrf();

        try {
            $this->tenantModel->update($id, ['status' => 'active']);
            $this->setFlash('success', 'Tenant activated');
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to activate tenant');
        }

        $this->redirect('/admin/tenants/' . $id);
    }

    public function plans()
    {
        $plans = $this->planModel->findAll([], 'price_monthly ASC');
        $this->render('admin/plans', ['plans' => $plans]);
    }

    public function storePlan()
    {
        $this->requireCsrf();

        try {
            $this->planModel->insert([
                'name' => $this->request->post('name'),
                'description' => $this->request->post('description'),
                'price_monthly' => $this->request->post('price_monthly'),
                'price_yearly' => $this->request->post('price_yearly'),
                'max_staff' => $this->request->post('max_staff'),
                'max_services' => $this->request->post('max_services'),
                'max_bookings_per_month' => $this->request->post('max_bookings_per_month'),
                'features' => json_encode([])
            ]);

            $this->setFlash('success', 'Plan created successfully');
        } catch (Exception $e) {
            error_log('Plan creation failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to create plan');
        }

        $this->redirect('/admin/plans');
    }
}
