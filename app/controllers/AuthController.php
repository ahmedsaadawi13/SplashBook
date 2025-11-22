<?php
// FILE: /app/controllers/AuthController.php

/**
 * AuthController - Handles user authentication
 *
 * Manages login, logout, and registration
 */
class AuthController extends Controller
{
    private $userModel;
    private $tenantModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->tenantModel = new TenantModel();
    }

    /**
     * Show login form
     */
    public function showLogin()
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', [], null);
    }

    /**
     * Process login
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $email = $this->request->post('email');
            $password = $this->request->post('password');
            $tenantSlug = $this->request->post('tenant_slug');

            // Validate input
            $errors = $this->validate($_POST, [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if (!empty($errors)) {
                $this->render('auth/login', ['errors' => $errors], null);
                return;
            }

            // Determine tenant ID
            $tenantId = null;
            if (!empty($tenantSlug)) {
                $tenant = $this->tenantModel->findBySlug($tenantSlug);
                if (!$tenant) {
                    $this->render('auth/login', ['error' => 'Invalid business account'], null);
                    return;
                }
                $tenantId = $tenant['id'];
            }

            // Authenticate user
            $user = $this->userModel->authenticate($email, $password, $tenantId);

            if ($user) {
                // Set session
                $this->session->set('user_id', $user['id']);
                $this->session->set('user', $user);
                $this->session->regenerate();

                // Redirect to dashboard
                $this->redirect('/dashboard');
            } else {
                $this->render('auth/login', ['error' => 'Invalid email or password'], null);
            }
        } else {
            $this->redirect('/login');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->session->destroy();
        $this->redirect('/login');
    }

    /**
     * Show registration form
     */
    public function showRegister()
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/register', [], null);
    }

    /**
     * Process registration
     */
    public function register()
    {
        if ($this->request->isPost()) {
            // Validate input
            $errors = $this->validate($_POST, [
                'business_name' => 'required|min:3|max:200',
                'first_name' => 'required|min:2|max:100',
                'last_name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'password' => 'required|min:8',
                'password_confirmation' => 'required',
                'phone' => 'phone'
            ]);

            // Check password confirmation
            if ($this->request->post('password') !== $this->request->post('password_confirmation')) {
                $errors['password_confirmation'] = ['Passwords do not match'];
            }

            if (!empty($errors)) {
                $this->render('auth/register', ['errors' => $errors, 'old' => $_POST], null);
                return;
            }

            try {
                $db = Database::getInstance();
                $db->beginTransaction();

                // Create tenant
                $slug = $this->tenantModel->generateSlug($this->request->post('business_name'));
                $apiKey = $this->tenantModel->generateApiKey();

                $tenantId = $this->tenantModel->insert([
                    'business_name' => $this->request->post('business_name'),
                    'slug' => $slug,
                    'email' => $this->request->post('email'),
                    'phone' => $this->request->post('phone'),
                    'api_key' => $apiKey,
                    'status' => 'active'
                ]);

                // Create user
                $userId = $this->userModel->createUser([
                    'tenant_id' => $tenantId,
                    'email' => $this->request->post('email'),
                    'password' => $this->request->post('password'),
                    'role' => 'tenant_admin',
                    'first_name' => $this->request->post('first_name'),
                    'last_name' => $this->request->post('last_name'),
                    'phone' => $this->request->post('phone')
                ]);

                // Create default subscription (trial)
                $subscriptionModel = new SubscriptionModel();
                $planModel = new PlanModel();
                $defaultPlan = $planModel->findOne(['name' => 'Starter']);

                if ($defaultPlan) {
                    $subscriptionModel->insert([
                        'tenant_id' => $tenantId,
                        'plan_id' => $defaultPlan['id'],
                        'status' => 'trialing',
                        'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
                        'current_period_start' => date('Y-m-d H:i:s'),
                        'current_period_end' => date('Y-m-d H:i:s', strtotime('+30 days'))
                    ]);
                }

                $db->commit();

                // Auto-login
                $user = $this->userModel->findById($userId);
                $this->session->set('user_id', $user['id']);
                $this->session->set('user', $user);
                $this->session->regenerate();

                $this->setFlash('success', 'Account created successfully! Welcome to SplashBook.');
                $this->redirect('/dashboard');

            } catch (Exception $e) {
                $db->rollBack();
                error_log('Registration failed: ' . $e->getMessage());
                $this->render('auth/register', ['error' => 'Registration failed. Please try again.', 'old' => $_POST], null);
            }
        } else {
            $this->redirect('/register');
        }
    }
}
