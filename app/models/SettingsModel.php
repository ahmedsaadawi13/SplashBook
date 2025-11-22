<?php
// FILE: /app/models/SettingsModel.php

/**
 * SettingsModel - Manages business settings
 *
 * Handles key-value settings storage for tenants
 */
class SettingsModel extends Model
{
    protected $table = 'business_settings';

    /**
     * Get setting value
     *
     * @param int $tenantId Tenant ID
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public function getSetting($tenantId, $key, $default = null)
    {
        $setting = $this->findOne([
            'tenant_id' => $tenantId,
            'setting_key' => $key
        ]);

        return $setting ? $setting['setting_value'] : $default;
    }

    /**
     * Set setting value
     *
     * @param int $tenantId Tenant ID
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success
     */
    public function setSetting($tenantId, $key, $value)
    {
        $existing = $this->findOne([
            'tenant_id' => $tenantId,
            'setting_key' => $key
        ]);

        if ($existing) {
            return $this->update($existing['id'], ['setting_value' => $value]) > 0;
        } else {
            return $this->insert([
                'tenant_id' => $tenantId,
                'setting_key' => $key,
                'setting_value' => $value
            ]) > 0;
        }
    }

    /**
     * Get all settings for tenant
     *
     * @param int $tenantId Tenant ID
     * @return array Settings as key-value pairs
     */
    public function getAllSettings($tenantId)
    {
        $settings = $this->findAll(['tenant_id' => $tenantId]);

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }

        return $result;
    }

    /**
     * Update multiple settings at once
     *
     * @param int $tenantId Tenant ID
     * @param array $settings Associative array of key => value
     * @return bool Success
     */
    public function updateSettings($tenantId, $settings)
    {
        try {
            foreach ($settings as $key => $value) {
                $this->setSetting($tenantId, $key, $value);
            }
            return true;
        } catch (Exception $e) {
            error_log('Failed to update settings: ' . $e->getMessage());
            return false;
        }
    }
}
