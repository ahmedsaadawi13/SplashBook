<!-- FILE: /app/views/staff/index.php -->
<div class="page-header">
    <h1>Staff Members</h1>
    <a href="/staff/create" class="btn btn-primary">Add Staff</a>
</div>

<div class="card">
    <?php if (empty($staff)): ?>
        <p class="text-muted">No staff members yet. Add your first staff member to get started.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Title</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $member): ?>
                    <tr>
                        <td><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></td>
                        <td><?php echo e($member['title']); ?></td>
                        <td><?php echo e($member['email']); ?></td>
                        <td><?php echo e($member['phone']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $member['is_active'] ? 'confirmed' : 'canceled'; ?>">
                                <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="/staff/<?php echo e($member['id']); ?>" class="btn btn-sm btn-secondary">View</a>
                            <a href="/staff/<?php echo e($member['id']); ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
