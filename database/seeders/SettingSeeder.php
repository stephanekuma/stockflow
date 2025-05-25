<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingsData = [
            'Social Media Settings' => [
                'text' => [
                    'Facebook Page',
                    'Twitter Handle',
                    'Instagram Profile',
                    'LinkedIn Profile',
                    'YouTube Channel',
                ],
            ],
            'General Settings' => [
                'text' => [
                    'Store Name',
                    'Address',
                    'Phone Number',
                    'Email',
                    'Website',
                    'Currency',
                    'Time Zone',
                    'Language',
                ],
                'select' => [
                    'Store Type' => [
                        'Grocery / Supermarket',
                        'Convenience Store',
                        'Specialty Store',
                        'Department Store',
                        'Discount Store',
                        'Off-Price Retailer',
                        'Warehouse Club',
                        'Hypermarket',
                        'Drugstore / Pharmacy',
                        'Dollar Store / Variety Store',
                        'Factory Outlet',
                        'Sporting Goods Store',
                        'Home Improvement Store',
                        'Pet Store',
                    ],
                ],
                'file' => [
                    'Store Logo',
                    'Favicon',
                ],
            ],
            'Inventory Settings' => [
                'number' => [
                    'Default Stock Level',
                    'Low Stock Threshold',
                ],
                'select' => [
                    'Out of Stock Behavior' => [
                        'Allow Backorders',
                        'Notify Customers',
                        'Hide Product',
                        'Disable Purchase',
                    ],
                ],
                'boolean' => [
                    'Inventory Tracking Enabled',
                    'Allow Backorders',
                ],
            ],
            'Shipping Settings' => [
                'text' => [
                    'Shipping Methods',
                    'Free Shipping Threshold',
                    'Shipping Zones',
                    'Handling Fees',
                ],
            ],
            'Security Settings' => [
                'boolean' => [
                    'Enable SSL',
                    'Require Strong Passwords',
                    'Enable CAPTCHA',
                    'Two-Factor Authentication',
                ],
                'text' => [
                    'Password Strength Requirements',
                    'Session Timeout',
                    'IP Whitelisting',
                ],
            ],
            'Notification Settings' => [
                'boolean' => [
                    'Email Notifications',
                    'SMS Notifications',
                    'Push Notifications',
                ],
                'text' => [
                    'Order Confirmation Template',
                    'Shipping Notification Template',
                    'Low Stock Alert Template',
                    'New Product Launch Template',
                ],
            ],
            'Analytics Settings' => [
                'text' => [
                    'Google Analytics ID',
                    'Facebook Pixel ID',
                    'Conversion Tracking',
                    'Sales Reports Frequency',
                ],
            ],
            'Customization Settings' => [
                'text' => [
                    'Theme Customization',
                    'Custom CSS',
                    'Custom JavaScript',
                    'Branding Options',
                ],
            ],
            'Legal Settings' => [
                'text' => [
                    'Privacy Policy',
                    'Terms of Service',
                    'Return Policy',
                    'Cookie Policy',
                ],
            ],
        ];

        foreach ($settingsData as $group => $types) {
            foreach ($types as $type => $values) {
                if ($type === 'select') {
                    foreach ($values as $selectKey => $selectOptions) {
                        Setting::query()->create([
                            'key' => $selectKey,
                            'group' => $group,
                            'type' => $type,
                            'attributes' => [
                                'options' => $selectOptions
                            ],
                        ]);
                    }
                } else {
                    foreach ($values as $value) {
                        Setting::query()->create([
                            'key' => $value,
                            'group' => $group,
                            'type' => $type,
                            'attributes' => null,
                        ]);
                    }
                }
            }
        }
    }
}
