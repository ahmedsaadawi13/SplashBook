<?php
// FILE: /app/controllers/CategoryController.php

class CategoryController extends Controller
{
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->categoryModel = new ServiceCategoryModel();
    }

    public function index()
    {
        $categories = $this->categoryModel->getCategoriesWithCounts($this->getTenantId());
        $this->render('categories/index', ['categories' => $categories]);
    }

    public function store()
    {
        $this->requireCsrf();

        try {
            $displayOrder = $this->categoryModel->getNextDisplayOrder($this->getTenantId());

            $this->categoryModel->insert([
                'tenant_id' => $this->getTenantId(),
                'name' => $this->request->post('name'),
                'description' => $this->request->post('description'),
                'display_order' => $displayOrder
            ]);

            $this->setFlash('success', 'Category created successfully');
        } catch (Exception $e) {
            error_log('Category creation failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to create category');
        }

        $this->redirect('/categories');
    }

    public function update($id)
    {
        $this->requireCsrf();
        $category = $this->categoryModel->findById($id);

        if (!$category || $category['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Category not found');
            $this->redirect('/categories');
        }

        try {
            $this->categoryModel->update($id, [
                'name' => $this->request->post('name'),
                'description' => $this->request->post('description')
            ]);

            $this->setFlash('success', 'Category updated successfully');
        } catch (Exception $e) {
            error_log('Category update failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to update category');
        }

        $this->redirect('/categories');
    }

    public function delete($id)
    {
        $this->requireCsrf();
        $category = $this->categoryModel->findById($id);

        if (!$category || $category['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Category not found');
            $this->redirect('/categories');
        }

        try {
            $this->categoryModel->delete($id);
            $this->setFlash('success', 'Category deleted successfully');
        } catch (Exception $e) {
            error_log('Category deletion failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to delete category');
        }

        $this->redirect('/categories');
    }
}
