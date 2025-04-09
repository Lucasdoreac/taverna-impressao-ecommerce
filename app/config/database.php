<?php

return [
    'indices' => [
        'products' => [
            'idx_products_is_featured',
            'idx_products_is_tested',
            'idx_products_is_active',
            'idx_products_created_at',
            'idx_products_category_id',
            'idx_products_slug',
            'idx_products_is_customizable',
            'idx_products_stock',
            'idx_products_availability',
            'ft_products_search'
        ],
        'product_images' => [
            'idx_product_images_product_id',
            'idx_product_images_is_main',
            'idx_product_images_product_main'
        ],
        'categories' => [
            'idx_categories_parent_id',
            'idx_categories_is_active',
            'idx_categories_display_order',
            'idx_categories_slug',
            'idx_categories_left_value',
            'idx_categories_right_value',
            'ft_categories_search'
        ],
        'orders' => [
            'idx_orders_user_id',
            'idx_orders_status',
            'idx_orders_created_at',
            'idx_orders_payment_status',
            'idx_orders_shipping_status'
        ],
        'order_items' => [
            'idx_order_items_order_id',
            'idx_order_items_product_id',
            'idx_order_items_status'
        ]
    ],
    'query_optimization' => [
        'enable_query_cache' => true,
        'query_cache_size' => '64M',
        'enable_join_buffer_size' => '2M',
        'table_open_cache' => 4000,
        'thread_cache_size' => 100,
        'max_connections' => 500
    ],
    'performance' => [
        'slow_query_log' => true,
        'slow_query_log_time' => 2.0,
        'log_queries_not_using_indexes' => true,
        'enable_profiling' => true
    ]
];