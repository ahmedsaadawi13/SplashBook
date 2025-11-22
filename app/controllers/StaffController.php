<?php
// FILE: /app/controllers/StaffController.php

class StaffController extends Controller
{
    private $staffModel;
    private $userModel;
    private $serviceModel;
    private $subscriptionModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->staffModel = new StaffModel();
        $this->userModel = new UserModel();
        $this->serviceModel = new ServiceModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    public function index()
    {
        $staff = $this->staffModel->getStaffByTenant($this->getTenantId(), false);
        $this->render('staff/index', ['staff' => $staff]);
    }

    public function create()
    {
        $canAdd = $this->subscriptionModel->canAddStaff($this->getTenantId());
        if (!$canAdd['allowed']) {
            $this->setFlash('error', $canAdd['message']);
            $this->redirect('/staff');
        }

        $services = $this->serviceModel->getServicesByTenant($this->getTenantId());
        $this->render('staff/create', ['services' => $services]);
    }

    public function store()
    {
        $this->requireCsrf();
        $tenantId = $this->getTenantId();

        $canAdd = $this->subscriptionModel->canAddStaff($tenantId);
        if (!$canAdd['allowed']) {
            $this->setFlash('error', $canAdd['message']);
            $this->redirect('/staff');
        }

        $errors = $this->validate($_POST, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'title' => 'required'
        ]);

        if (!empty($errors)) {
            $this->setFlash('error', 'Please correct the errors');
            $this->redirect('/staff/create');
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $userId = $this->userModel->createUser([
                'tenant_id' => $tenantId,
                'email' => $this->request->post('email'),
                'password' => $this->request->post('password', 'password123'),
                'role' => 'staff',
                'first_name' => $this->request->post('first_name'),
                'last_name' => $this->request->post('last_name'),
                'phone' => $this->request->post('phone')
            ]);

            $staffId = $this->staffModel->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'title' => $this->request->post('title'),
                'bio' => $this->request->post('bio')
            ]);

            if ($this->request->post('service_ids')) {
                $this->staffModel->updateStaffServices($staffId, $this->request->post('service_ids'));
            }

            $db->commit();
            $this->setFlash('success', 'Staff member added successfully');
            $this->redirect('/staff');

        } catch (Exception $e) {
            $db->rollBack();
            error_log('Staff creation failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to add staff member');
            $this->redirect('/staff/create');
        }
    }

    public function show($id)
    {
        $staff = $this->staffModel->getStaffWithUser($id);
        if (!$staff || $staff['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Staff not found');
            $this->redirect('/staff');
        }

        $services = $this->staffModel->getStaffServices($id);
        $workingHours = $this->staffModel->getWorkingHours($id);
        $breaks = $this->staffModel->getBreaks($id);

        $this->render('staff/show', compact('staff', 'services', 'workingHours', 'breaks'));
    }

    public function edit($id)
    {
        $staff = $this->staffModel->getStaffWithUser($id);
        if (!$staff || $staff['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Staff not found');
            $this->redirect('/staff');
        }

        $services = $this->serviceModel->getServicesByTenant($this->getTenantId());
        $assignedServices = array_column($this->staffModel->getStaffServices($id), 'id');

        $this->render('staff/edit', compact('staff', 'services', 'assignedServices'));
    }

    public function update($id)
    {
        $this->requireCsrf();
        $staff = $this->staffModel->findById($id);
        if (!$staff || $staff['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Staff not found');
            $this->redirect('/staff');
        }

        try {
            $this->staffModel->update($id, [
                'title' => $this->request->post('title'),
                'bio' => $this->request->post('bio'),
                'is_active' => $this->request->post('is_active', 1)
            ]);

            if ($staff['user_id']) {
                $this->userModel->update($staff['user_id'], [
                    'first_name' => $this->request->post('first_name'),
                    'last_name' => $this->request->post('last_name'),
                    'phone' => $this->request->post('phone')
                ]);
            }

            if ($this->request->post('service_ids')) {
                $this->staffModel->updateStaffServices($id, $this->request->post('service_ids'));
            }

            $this->setFlash('success', 'Staff updated successfully');
            $this->redirect('/staff/' . $id);

        } catch (Exception $e) {
            error_log('Staff update failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to update staff');
            $this->redirect('/staff/' . $id . '/edit');
        }
    }

    public function schedule($id)
    {
        $staff = $this->staffModel->getStaffWithUser($id);
        if (!$staff || $staff['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Staff not found');
            $this->redirect('/staff');
        }

        $workingHours = $this->staffModel->getWorkingHours($id);
        $breaks = $this->staffModel->getBreaks($id);

        // Organize by day
        $schedule = [];
        foreach ($workingHours as $hours) {
            $schedule[$hours['day_of_week']] = $hours;
        }

        $this->render('staff/schedule', compact('staff', 'schedule', 'breaks'));
    }

    public function updateSchedule($id)
    {
        $this->requireCsrf();
        $staff = $this->staffModel->findById($id);
        if (!$staff || $staff['tenant_id'] != $this->getTenantId()) {
            $this->json(['success' => false], 404);
        }

        try {
            $schedule = $this->request->post('schedule', []);
            $this->staffModel->updateWorkingHours($id, $schedule);

            $this->setFlash('success', 'Schedule updated successfully');
            $this->redirect('/staff/' . $id);

        } catch (Exception $e) {
            error_log('Schedule update failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to update schedule');
            $this->redirect('/staff/' . $id . '/schedule');
        }
    }

    public function delete($id)
    {
        $this->requireCsrf();
        $staff = $this->staffModel->findById($id);
        if (!$staff || $staff['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Staff not found');
            $this->redirect('/staff');
        }

        try {
            $this->staffModel->delete($id);
            $this->setFlash('success', 'Staff deleted successfully');
        } catch (Exception $e) {
            error_log('Staff deletion failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to delete staff');
        }

        $this->redirect('/staff');
    }
}
