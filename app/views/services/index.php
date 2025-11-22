<!-- FILE: /app/views/services/index.php -->
<div class="page-header">
    <h1>Services</h1>
    <div>
        <a href="/categories" class="btn btn-secondary">Manage Categories</a>
        <a href="/services/create" class="btn btn-primary">Add Service</a>
    </div>
</div>

<div class="card">
    <?php if (empty($services)): ?>
        <p class="text-muted">No services yet. Add your first service to get started.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Category</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td>
                            <span class="badge" style="background-color: <?php echo e($service['color']); ?>">●</span>
                            <?php echo e($service['name']); ?>
                        </td>
                        <td><?php echo e($service['category_name'] ?? 'Uncategorized'); ?></td>
                        <td><?php echo e($service['duration_minutes']); ?> min</td>
                        <td>$<?php echo e(number_format($service['base_price'], 2)); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $service['is_active'] ? 'confirmed' : 'canceled'; ?>">
                                <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="/services/<?php echo e($service['id']); ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
