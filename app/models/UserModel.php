<?php
// FILE: /app/models/UserModel.php

/**
 * UserModel - Manages user authentication and accounts
 *
 * Handles user CRUD operations, authentication, and role management
 */
class UserModel extends Model
{
    protected $table = 'users';

    /**
     * Find user by email and tenant
     *
     * @param string $email User email
     * @param int $tenantId Tenant ID (null for platform admin)
     * @return array|null User data
     */
    public function findByEmailAndTenant($email, $tenantId)
    {
        $sql = "SELECT * FROM users WHERE email = ? AND (tenant_id = ? OR (tenant_id IS NULL AND ? IS NULL)) LIMIT 1";
        return $this->db->queryOne($sql, [$email, $tenantId, $tenantId]);
    }

    /**
     * Authenticate user
     *
     * @param string $email User email
     * @param string $password User password
     * @param int $tenantId Tenant ID (null for platform admin)
     * @return array|null User data if authenticated, null otherwise
     */
    public function authenticate($email, $password, $tenantId = null)
    {
        $user = $this->findByEmailAndTenant($email, $tenantId);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $this->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
            return $user;
        }

        return null;
    }

    /**
     * Create a new user with hashed password
     *
     * @param array $data User data (must include password)
     * @return int User ID
     */
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $this->insert($data);
    }

    /**
     * Update user password
     *
     * @param int $userId User ID
     * @param string $newPassword New password
     * @return int Number of affected rows
     */
    public function updatePassword($userId, $newPassword)
    {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password_hash' => $passwordHash]);
    }

    /**
     * Get users by tenant
     *
     * @param int $tenantId Tenant ID
     * @param string $role Filter by role (optional)
     * @return array Users list
     */
    public function getUsersByTenant($tenantId, $role = null)
    {
        if ($role) {
            return $this->findAll(['tenant_id' => $tenantId, 'role' => $role], 'first_name, last_name');
        }
        return $this->findAll(['tenant_id' => $tenantId], 'first_name, last_name');
    }

    /**
     * Get user with full name
     *
     * @param int $userId User ID
     * @return array|null User data with full_name field
     */
    public function getUserWithFullName($userId)
    {
        $user = $this->findById($userId);
        if ($user) {
            $user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        }
        return $user;
    }

    /**
     * Check if email exists for tenant
     *
     * @param string $email Email address
     * @param int $tenantId Tenant ID
     * @param int $exceptUserId User ID to except (for updates)
     * @return bool
     */
    public function emailExists($email, $tenantId, $exceptUserId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND tenant_id = ?";
        $params = [$email, $tenantId];

        if ($exceptUserId) {
            $sql .= " AND id != ?";
            $params[] = $exceptUserId;
        }

        $result = $this->db->queryOne($sql, $params);
        return $result['count'] > 0;
    }
}
