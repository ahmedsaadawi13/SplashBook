<?php
// FILE: /config/routes.php

/**
 * Application routes
 *
 * Define all application routes here
 */

// Public routes
$router->get('/', 'HomeController@index');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');

// Public booking page
$router->get('/book/{slug}', 'PublicBookingController@index');
$router->get('/book/{slug}/services', 'PublicBookingController@services');
$router->post('/book/{slug}/availability', 'PublicBookingController@availability');
$router->post('/book/{slug}/create', 'PublicBookingController@create');
$router->get('/book/{slug}/confirmation/{reference}', 'PublicBookingController@confirmation');

// Dashboard (authenticated users)
$router->get('/dashboard', 'DashboardController@index');

// Bookings management
$router->get('/bookings', 'BookingController@index');
$router->get('/bookings/create', 'BookingController@create');
$router->post('/bookings/store', 'BookingController@store');
$router->get('/bookings/{id}', 'BookingController@show');
$router->get('/bookings/{id}/edit', 'BookingController@edit');
$router->post('/bookings/{id}/update', 'BookingController@update');
$router->post('/bookings/{id}/delete', 'BookingController@delete');
$router->post('/bookings/{id}/status', 'BookingController@updateStatus');

// Calendar
$router->get('/calendar', 'CalendarController@index');
$router->get('/calendar/day', 'CalendarController@day');
$router->get('/calendar/week', 'CalendarController@week');
$router->get('/calendar/data', 'CalendarController@data');

// Staff management
$router->get('/staff', 'StaffController@index');
$router->get('/staff/create', 'StaffController@create');
$router->post('/staff/store', 'StaffController@store');
$router->get('/staff/{id}', 'StaffController@show');
$router->get('/staff/{id}/edit', 'StaffController@edit');
$router->post('/staff/{id}/update', 'StaffController@update');
$router->post('/staff/{id}/delete', 'StaffController@delete');
$router->get('/staff/{id}/schedule', 'StaffController@schedule');
$router->post('/staff/{id}/schedule/update', 'StaffController@updateSchedule');

// Services management
$router->get('/services', 'ServiceController@index');
$router->get('/services/create', 'ServiceController@create');
$router->post('/services/store', 'ServiceController@store');
$router->get('/services/{id}/edit', 'ServiceController@edit');
$router->post('/services/{id}/update', 'ServiceController@update');
$router->post('/services/{id}/delete', 'ServiceController@delete');

// Service categories
$router->get('/categories', 'CategoryController@index');
$router->post('/categories/store', 'CategoryController@store');
$router->post('/categories/{id}/update', 'CategoryController@update');
$router->post('/categories/{id}/delete', 'CategoryController@delete');

// Clients management
$router->get('/clients', 'ClientController@index');
$router->get('/clients/create', 'ClientController@create');
$router->post('/clients/store', 'ClientController@store');
$router->get('/clients/{id}', 'ClientController@show');
$router->get('/clients/{id}/edit', 'ClientController@edit');
$router->post('/clients/{id}/update', 'ClientController@update');
$router->post('/clients/{id}/delete', 'ClientController@delete');

// Business settings
$router->get('/settings', 'SettingsController@index');
$router->post('/settings/update', 'SettingsController@update');
$router->post('/settings/logo', 'SettingsController@uploadLogo');

// Subscription & billing (tenant admin)
$router->get('/subscription', 'SubscriptionController@index');
$router->get('/subscription/plans', 'SubscriptionController@plans');
$router->post('/subscription/change', 'SubscriptionController@changePlan');
$router->get('/invoices', 'InvoiceController@index');
$router->get('/invoices/{id}', 'InvoiceController@show');
$router->post('/invoices/{id}/pay', 'InvoiceController@pay');

// Platform admin routes
$router->get('/admin/tenants', 'AdminController@tenants');
$router->get('/admin/tenants/{id}', 'AdminController@tenantDetail');
$router->post('/admin/tenants/{id}/suspend', 'AdminController@suspendTenant');
$router->post('/admin/tenants/{id}/activate', 'AdminController@activateTenant');
$router->get('/admin/plans', 'AdminController@plans');
$router->post('/admin/plans/store', 'AdminController@storePlan');

// API endpoints (for external integrations)
$router->get('/api/availability', 'ApiController@availability');
$router->post('/api/booking', 'ApiController@createBooking');

// File uploads
$router->post('/upload/image', 'UploadController@image');
$router->post('/upload/document', 'UploadController@document');
