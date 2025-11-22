<?php
// FILE: /app/controllers/SettingsController.php

class SettingsController extends Controller
{
    private $tenantModel;
    private $settingsModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['tenant_admin']);
        $this->tenantModel = new TenantModel();
        $this->settingsModel = new SettingsModel();
    }

    public function index()
    {
        $tenantId = $this->getTenantId();
        $tenant = $this->tenantModel->findById($tenantId);
        $settings = $this->settingsModel->getAllSettings($tenantId);

        $this->render('settings/index', compact('tenant', 'settings'));
    }

    public function update()
    {
        $this->requireCsrf();
        $tenantId = $this->getTenantId();

        try {
            // Update tenant info
            $this->tenantModel->update($tenantId, [
                'business_name' => $this->request->post('business_name'),
                'description' => $this->request->post('description'),
                'address' => $this->request->post('address'),
                'city' => $this->request->post('city'),
                'state' => $this->request->post('state'),
                'postal_code' => $this->request->post('postal_code'),
                'phone' => $this->request->post('phone'),
                'email' => $this->request->post('email'),
                'website' => $this->request->post('website'),
                'default_timezone' => $this->request->post('default_timezone'),
                'default_currency' => $this->request->post('default_currency')
            ]);

            // Update settings
            $this->settingsModel->updateSettings($tenantId, [
                'booking_advance_days' => $this->request->post('booking_advance_days', 60),
                'min_cancellation_hours' => $this->request->post('min_cancellation_hours', 24),
                'business_hours_start' => $this->request->post('business_hours_start', '09:00'),
                'business_hours_end' => $this->request->post('business_hours_end', '17:00')
            ]);

            $this->setFlash('success', 'Settings updated successfully');
            $this->redirect('/settings');

        } catch (Exception $e) {
            error_log('Settings update failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to update settings');
            $this->redirect('/settings');
        }
    }

    public function uploadLogo()
    {
        $this->requireCsrf();
        $tenantId = $this->getTenantId();

        if (!$this->request->hasFile('logo')) {
            $this->setFlash('error', 'No file uploaded');
            $this->redirect('/settings');
        }

        $config = require __DIR__ . '/../../config/app.php';
        $uploader = new FileUpload($config['allowed_image_types'], $config['max_upload_size']);

        $result = $uploader->upload($this->request->file('logo'), 'logos');

        if ($result['success']) {
            try {
                // Delete old logo
                $tenant = $this->tenantModel->findById($tenantId);
                if ($tenant['logo']) {
                    $uploader->delete($tenant['logo'], 'logos');
                }

                // Update tenant
                $this->tenantModel->update($tenantId, ['logo' => $result['path']]);

                $this->setFlash('success', 'Logo uploaded successfully');
            } catch (Exception $e) {
                error_log('Logo upload failed: ' . $e->getMessage());
                $this->setFlash('error', 'Failed to save logo');
            }
        } else {
            $this->setFlash('error', $result['message']);
        }

        $this->redirect('/settings');
    }
}
