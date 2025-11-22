<?php
// FILE: /app/controllers/ClientController.php

class ClientController extends Controller
{
    private $clientModel;
    private $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->clientModel = new ClientModel();
        $this->bookingModel = new BookingModel();
    }

    public function index()
    {
        $search = $this->request->get('search');
        $page = (int) $this->request->get('page', 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $clients = $this->clientModel->getClientsByTenant($this->getTenantId(), $search, $limit, $offset);
        $this->render('clients/index', compact('clients', 'search'));
    }

    public function create()
    {
        $this->render('clients/create');
    }

    public function store()
    {
        $this->requireCsrf();

        $errors = $this->validate($_POST, [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'email',
            'phone' => 'phone'
        ]);

        if (!empty($errors)) {
            $this->setFlash('error', 'Please correct the errors');
            $this->redirect('/clients/create');
        }

        try {
            $this->clientModel->insert([
                'tenant_id' => $this->getTenantId(),
                'first_name' => $this->request->post('first_name'),
                'last_name' => $this->request->post('last_name'),
                'email' => $this->request->post('email'),
                'phone' => $this->request->post('phone'),
                'notes' => $this->request->post('notes')
            ]);

            $this->setFlash('success', 'Client added successfully');
            $this->redirect('/clients');

        } catch (Exception $e) {
            error_log('Client creation failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to add client');
            $this->redirect('/clients/create');
        }
    }

    public function show($id)
    {
        $client = $this->clientModel->getClientWithStats($id);
        if (!$client || $client['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Client not found');
            $this->redirect('/clients');
        }

        $bookings = $this->bookingModel->getBookingsByClient($id);
        $this->render('clients/show', compact('client', 'bookings'));
    }

    public function edit($id)
    {
        $client = $this->clientModel->findById($id);
        if (!$client || $client['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Client not found');
            $this->redirect('/clients');
        }

        $this->render('clients/edit', ['client' => $client]);
    }

    public function update($id)
    {
        $this->requireCsrf();
        $client = $this->clientModel->findById($id);
        if (!$client || $client['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Client not found');
            $this->redirect('/clients');
        }

        try {
            $this->clientModel->update($id, [
                'first_name' => $this->request->post('first_name'),
                'last_name' => $this->request->post('last_name'),
                'email' => $this->request->post('email'),
                'phone' => $this->request->post('phone'),
                'notes' => $this->request->post('notes')
            ]);

            $this->setFlash('success', 'Client updated successfully');
            $this->redirect('/clients/' . $id);

        } catch (Exception $e) {
            error_log('Client update failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to update client');
            $this->redirect('/clients/' . $id . '/edit');
        }
    }

    public function delete($id)
    {
        $this->requireCsrf();
        $client = $this->clientModel->findById($id);
        if (!$client || $client['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Client not found');
            $this->redirect('/clients');
        }

        try {
            $this->clientModel->delete($id);
            $this->setFlash('success', 'Client deleted successfully');
        } catch (Exception $e) {
            error_log('Client deletion failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to delete client');
        }

        $this->redirect('/clients');
    }
}
