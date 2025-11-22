<!-- FILE: /app/views/clients/index.php -->
<div class="page-header">
    <h1>Clients</h1>
    <a href="/clients/create" class="btn btn-primary">Add Client</a>
</div>

<div class="card">
    <form method="GET" action="/clients" class="filters-form">
        <div class="form-row">
            <div class="form-group">
                <input type="text" name="search" class="form-control" placeholder="Search clients..." value="<?php echo e($search ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-secondary">Search</button>
        </div>
    </form>

    <?php if (empty($clients)): ?>
        <p class="text-muted">No clients found</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Total Bookings</th>
                    <th>Completed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?php echo e($client['first_name'] . ' ' . $client['last_name']); ?></td>
                        <td><?php echo e($client['email']); ?></td>
                        <td><?php echo e($client['phone']); ?></td>
                        <td><?php echo e($client['total_bookings']); ?></td>
                        <td><?php echo e($client['completed_bookings']); ?></td>
                        <td>
                            <a href="/clients/<?php echo e($client['id']); ?>" class="btn btn-sm btn-secondary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
