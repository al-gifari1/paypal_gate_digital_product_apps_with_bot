<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\View;
use App\Services\SettingsService;

class SchemaApiController
{
    /**
     * Generate dynamic OpenAPI 3.1.0 specification for LLM Agents & Custom GPTs.
     * GET /api/v1/openapi.json
     */
    public function openapi(): void
    {
        $appUrl = SettingsService::getAppUrl();
        $storeTitle = SettingsService::get('site_title', 'Digital Vault & PayPal Platform');

        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => "{$storeTitle} - System Automation API",
                'version' => '2.0.0',
                'description' => 'Google AIP-compliant REST API designed for autonomous LLM agents, Custom GPTs, Claude Tools, and e-commerce inventory sync pipelines.',
            ],
            'servers' => [
                [
                    'url' => $appUrl,
                    'description' => 'Active Platform Host',
                ]
            ],
            'security' => [
                ['ApiKeyAuth' => []],
                ['BearerAuth' => []],
            ],
            'paths' => [
                '/api/v1/stats' => [
                    'get' => [
                        'summary' => 'Get Storefront Telemetry & Inventory Metrics',
                        'description' => 'Returns gross revenue, completed orders count, low-stock warnings, and card pool totals.',
                        'operationId' => 'getStoreStats',
                        'responses' => [
                            '200' => ['description' => 'Store telemetry successfully retrieved.'],
                            '401' => ['description' => 'Unauthorized - Invalid or missing API key.'],
                        ]
                    ]
                ],
                '/api/v1/products' => [
                    'get' => [
                        'summary' => 'List Digital Products',
                        'description' => 'Retrieve paginated catalog of digital goods with available stock and sales count.',
                        'operationId' => 'listProducts',
                        'parameters' => [
                            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                            ['name' => 'page_size', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20]],
                            ['name' => 'type', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['downloadable_file', 'card_license']]],
                            ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Product array with pagination metadata.'],
                        ]
                    ],
                    'post' => [
                        'summary' => 'Create Digital Product (LLM Action)',
                        'description' => 'Creates a new product in the store catalog with optional instant card inventory.',
                        'operationId' => 'createProduct',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'price'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'example' => 'VIP Trading Signals Discord Access'],
                                            'price' => ['type' => 'number', 'format' => 'float', 'example' => 29.99],
                                            'currency' => ['type' => 'string', 'default' => 'USD', 'example' => 'USD'],
                                            'description' => ['type' => 'string', 'example' => 'Full access key delivered instantly upon payment.'],
                                            'product_type' => ['type' => 'string', 'enum' => ['downloadable_file', 'card_license'], 'default' => 'card_license'],
                                            'image_url' => ['type' => 'string', 'format' => 'uri', 'example' => 'https://example.com/cover.png'],
                                            'initial_keys' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                                'description' => 'Optional array of license keys or account serials to seed the inventory pool.',
                                                'example' => ['KEY-1111-2222', 'KEY-3333-4444']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Product created successfully.'],
                            '400' => ['description' => 'Invalid arguments provided.'],
                        ]
                    ]
                ],
                '/api/v1/products/{id}' => [
                    'get' => [
                        'summary' => 'Get Single Product Details',
                        'description' => 'Retrieve detailed information, revenue analytics, and stock count for a specific product.',
                        'operationId' => 'getProductById',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Product details.'],
                            '404' => ['description' => 'Product not found.'],
                        ]
                    ],
                    'put' => [
                        'summary' => 'Update Product Details',
                        'description' => 'Modify name, price, description, image, or active status.',
                        'operationId' => 'updateProduct',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'price' => ['type' => 'number'],
                                            'currency' => ['type' => 'string'],
                                            'description' => ['type' => 'string'],
                                            'image_url' => ['type' => 'string'],
                                            'is_active' => ['type' => 'boolean']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Product updated successfully.'],
                        ]
                    ],
                    'delete' => [
                        'summary' => 'Delete Product',
                        'description' => 'Remove a digital product from the catalog.',
                        'operationId' => 'deleteProduct',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Product deleted.'],
                        ]
                    ]
                ],
                '/api/v1/products/{id}/cards' => [
                    'get' => [
                        'summary' => 'Query Product License Cards Inventory',
                        'description' => 'Get inventory counts and optionally list available keys.',
                        'operationId' => 'getCardPool',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                            ['name' => 'include_available', 'in' => 'query', 'schema' => ['type' => 'boolean', 'default' => false]]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Inventory counts.'],
                        ]
                    ],
                    'post' => [
                        'summary' => 'Bulk Append License Keys / Cards (LLM Action)',
                        'description' => 'Import batch license keys or account serials into the pool.',
                        'operationId' => 'bulkAddCards',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'keys' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                                'example' => ['KEY-AAAA', 'KEY-BBBB']
                                            ],
                                            'card_data' => [
                                                'type' => 'string',
                                                'description' => 'Alternative newline-separated string of keys'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Keys imported successfully.'],
                        ]
                    ]
                ],
                '/api/v1/orders' => [
                    'get' => [
                        'summary' => 'List Orders & Transactions',
                        'description' => 'Query completed customer sales and delivery status.',
                        'operationId' => 'listOrders',
                        'parameters' => [
                            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                            ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['completed', 'pending', 'refunded']]]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Order history array.'],
                        ]
                    ]
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                        'description' => 'Master API Secret Key configured in Admin Panel.'
                    ],
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'Standard HTTP Bearer Token'
                    ]
                ]
            ]
        ];

        View::json($spec);
    }
}
