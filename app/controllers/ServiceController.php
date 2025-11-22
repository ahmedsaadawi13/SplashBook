<?php
// FILE: /app/controllers/ServiceController.php

class ServiceController extends Controller
{
    private $serviceModel;
    private $categoryModel;
    private $subscriptionModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->serviceModel = new ServiceModel();
        $this->categoryModel = new ServiceCategoryModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    public function index()
    {
        $services = $this->serviceModel->getServicesByTenant($this->getTenantId(), false);
        $categories = $this->categoryModel->getCategoriesByTenant($this->getTenantId());
        $this->render('services/index', compact('services', 'categories'));
    }

    public function create()
    {
        $canAdd = $this->subscriptionModel->canAddService($this->getTenantId());
        if (!$canAdd['allowed']) {
            $this->setFlash('error', $canAdd['message']);
            $this->redirect('/services');
        }

        $categories = $this->categoryModel->getCategoriesByTenant($this->getTenantId());
        $this->render('services/create', ['categories' => $categories]);
    }

    public function store()
    {
        $this->requireCsrf();
        $tenantId = $this->getTenantId();

        $canAdd = $this->subscriptionModel->canAddService($tenantId);
        if (!$canAdd['allowed']) {
            $this->setFlash('error', $canAdd['message']);
            $this->redirect('/services');
        }

        $errors = $this->validate($_POST, [
            'name' => 'required|max:200',
            'duration_minutes' => 'required|integer',
            'base_price' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            $this->setFlash('error', 'Please correct the errors');
            $this->redirect('/services/create');
        }

        try {
            $this->serviceModel->insert([
                'tenant_id' => $tenantId,
                'category_id' => $this->request->post('category_id') ?: null,
                'name' => $this->request->post('name'),
                'description' => $this->request->post('description'),
                'duration_minutes' => $this->request->post('duration_minutes'),
                'base_price' => $this->request->post('base_price'),
                'color' => $this->request->post('color', '#3498db'),
                'is_online' => $this->request->post('is_online', 0)
            ]);

            $this->setFlash('success', 'Service created successfully');
            $this->redirect('/services');

        } catch (Exception $e) {
            error_log('Service creation failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to create service');
            $this->redirect('/services/create');
        }
    }

    public function edit($id)
    {
        $service = $this->serviceModel->findById($id);
        if (!$service || $service['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Service not found');
            $this->redirect('/services');
        }

        $categories = $this->categoryModel->getCategoriesByTenant($this->getTenantId());
        $this->render('services/edit', compact('service', 'categories'));
    }

    public function update($id)
    {
        $this->requireCsrf();
        $service = $this->serviceModel->findById($id);
        if (!$service || $service['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Service not found');
            $this->redirect('/services');
        }

        try {
            $this->serviceModel->update($id, [
                'category_id' => $this->request->post('category_id') ?: null,
                'name' => $this->request->post('name'),
                'description' => $this->request->post('description'),
                'duration_minutes' => $this->request->post('duration_minutes'),
                'base_price' => $this->request->post('base_price'),
                'color' => $this->request->post('color'),
                'is_online' => $this->request->post('is_online', 0),
                'is_active' => $this->request->post('is_active', 1)
            ]);

            $this->setFlash('success', 'Service updated successfully');
            $this->redirect('/services');

        } catch (Exception $e) {
            error_log('Service update failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to update service');
            $this->redirect('/services/' . $id . '/edit');
        }
    }

    public function delete($id)
    {
        $this->requireCsrf();
        $service = $this->serviceModel->findById($id);
        if (!$service || $service['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Service not found');
            $this->redirect('/services');
        }

        try {
            $this->serviceModel->delete($id);
            $this->setFlash('success', 'Service deleted successfully');
        } catch (Exception $e) {
            error_log('Service deletion failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to delete service');
        }

        $this->redirect('/services');
    }
}
