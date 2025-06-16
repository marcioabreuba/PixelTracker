<?php
// Teste do Webhook Shopify
echo "<h1>🧪 Teste do Webhook Shopify</h1>";

echo "<h2>📋 Verificações:</h2>";

// 1. Verificar se o endpoint existe
$webhookUrl = 'https://traqueamentophp.onrender.com/webhook/shopify';
echo "<p><strong>1. Testando endpoint:</strong> $webhookUrl</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 405) {
    echo "<p>✅ <strong>Endpoint OK</strong> - Retornou 405 (Method Not Allowed) para GET, isso é correto!</p>";
} elseif ($httpCode == 200) {
    echo "<p>✅ <strong>Endpoint OK</strong> - Retornou 200</p>";
} else {
    echo "<p>❌ <strong>Problema</strong> - HTTP Code: $httpCode</p>";
}

// 2. Simular dados do Shopify
echo "<h2>📦 Simulando Webhook do Shopify:</h2>";

$shopifyData = [
    'id' => 123456789,
    'email' => 'teste@salveterrah.com.br',
    'created_at' => date('c'),
    'updated_at' => date('c'),
    'number' => 1001,
    'note' => null,
    'token' => 'test_token_123',
    'gateway' => 'manual',
    'test' => true,
    'total_price' => '99.90',
    'subtotal_price' => '99.90',
    'total_weight' => 0,
    'total_tax' => '0.00',
    'taxes_included' => false,
    'currency' => 'BRL',
    'financial_status' => 'paid',
    'confirmed' => true,
    'total_discounts' => '0.00',
    'buyer_accepts_marketing' => false,
    'name' => '#1001',
    'referring_site' => 'https://salveterrah.com.br',
    'landing_site' => '/products/produto-teste',
    'cancelled_at' => null,
    'cancel_reason' => null,
    'total_price_usd' => '18.50',
    'checkout_token' => 'checkout_test_123',
    'reference' => null,
    'user_id' => null,
    'location_id' => null,
    'source_identifier' => null,
    'source_url' => null,
    'processed_at' => date('c'),
    'device_id' => null,
    'phone' => '+5522999999999',
    'customer_url' => 'https://salveterrah.com.br/account',
    'order_number' => 1001,
    'discount_codes' => [],
    'note_attributes' => [],
    'payment_gateway_names' => ['manual'],
    'processing_method' => 'direct',
    'checkout_id' => 987654321,
    'source_name' => 'web',
    'fulfillment_status' => null,
    'tax_lines' => [],
    'tags' => '',
    'contact_email' => 'teste@salveterrah.com.br',
    'order_status_url' => 'https://salveterrah.com.br/orders/test123',
    'presentment_currency' => 'BRL',
    'total_line_items_price_set' => [
        'shop_money' => [
            'amount' => '99.90',
            'currency_code' => 'BRL'
        ],
        'presentment_money' => [
            'amount' => '99.90',
            'currency_code' => 'BRL'
        ]
    ],
    'total_discounts_set' => [
        'shop_money' => [
            'amount' => '0.00',
            'currency_code' => 'BRL'
        ],
        'presentment_money' => [
            'amount' => '0.00',
            'currency_code' => 'BRL'
        ]
    ],
    'total_shipping_price_set' => [
        'shop_money' => [
            'amount' => '0.00',
            'currency_code' => 'BRL'
        ],
        'presentment_money' => [
            'amount' => '0.00',
            'currency_code' => 'BRL'
        ]
    ],
    'subtotal_price_set' => [
        'shop_money' => [
            'amount' => '99.90',
            'currency_code' => 'BRL'
        ],
        'presentment_money' => [
            'amount' => '99.90',
            'currency_code' => 'BRL'
        ]
    ],
    'total_price_set' => [
        'shop_money' => [
            'amount' => '99.90',
            'currency_code' => 'BRL'
        ],
        'presentment_money' => [
            'amount' => '99.90',
            'currency_code' => 'BRL'
        ]
    ],
    'total_tax_set' => [
        'shop_money' => [
            'amount' => '0.00',
            'currency_code' => 'BRL'
        ],
        'presentment_money' => [
            'amount' => '0.00',
            'currency_code' => 'BRL'
        ]
    ],
    'line_items' => [
        [
            'id' => 987654321,
            'variant_id' => 123456789,
            'title' => 'Produto Teste',
            'quantity' => 1,
            'sku' => 'TESTE-001',
            'variant_title' => null,
            'vendor' => 'Salve Terra',
            'fulfillment_service' => 'manual',
            'product_id' => 456789123,
            'requires_shipping' => true,
            'taxable' => true,
            'gift_card' => false,
            'name' => 'Produto Teste',
            'variant_inventory_management' => 'shopify',
            'properties' => [],
            'product_exists' => true,
            'fulfillable_quantity' => 1,
            'grams' => 500,
            'price' => '99.90',
            'total_discount' => '0.00',
            'fulfillment_status' => null,
            'price_set' => [
                'shop_money' => [
                    'amount' => '99.90',
                    'currency_code' => 'BRL'
                ],
                'presentment_money' => [
                    'amount' => '99.90',
                    'currency_code' => 'BRL'
                ]
            ],
            'total_discount_set' => [
                'shop_money' => [
                    'amount' => '0.00',
                    'currency_code' => 'BRL'
                ],
                'presentment_money' => [
                    'amount' => '0.00',
                    'currency_code' => 'BRL'
                ]
            ],
            'discount_allocations' => [],
            'duties' => [],
            'admin_graphql_api_id' => 'gid://shopify/LineItem/987654321',
            'tax_lines' => []
        ]
    ],
    'shipping_address' => [
        'first_name' => 'João',
        'address1' => 'Rua Teste, 123',
        'phone' => '+5522999999999',
        'city' => 'Araruama',
        'zip' => '28979-000',
        'province' => 'Rio de Janeiro',
        'country' => 'Brazil',
        'last_name' => 'Silva',
        'address2' => 'Apt 101',
        'company' => null,
        'latitude' => -22.8732,
        'longitude' => -42.3396,
        'name' => 'João Silva',
        'country_code' => 'BR',
        'province_code' => 'RJ'
    ],
    'billing_address' => [
        'first_name' => 'João',
        'address1' => 'Rua Teste, 123',
        'phone' => '+5522999999999',
        'city' => 'Araruama',
        'zip' => '28979-000',
        'province' => 'Rio de Janeiro',
        'country' => 'Brazil',
        'last_name' => 'Silva',
        'address2' => 'Apt 101',
        'company' => null,
        'latitude' => -22.8732,
        'longitude' => -42.3396,
        'name' => 'João Silva',
        'country_code' => 'BR',
        'province_code' => 'RJ'
    ],
    'customer' => [
        'id' => 555666777,
        'email' => 'teste@salveterrah.com.br',
        'accepts_marketing' => false,
        'created_at' => '2025-01-01T00:00:00-03:00',
        'updated_at' => date('c'),
        'first_name' => 'João',
        'last_name' => 'Silva',
        'orders_count' => 1,
        'state' => 'enabled',
        'total_spent' => '99.90',
        'last_order_id' => 123456789,
        'note' => null,
        'verified_email' => true,
        'multipass_identifier' => null,
        'tax_exempt' => false,
        'phone' => '+5522999999999',
        'tags' => '',
        'last_order_name' => '#1001',
        'currency' => 'BRL',
        'accepts_marketing_updated_at' => '2025-01-01T00:00:00-03:00',
        'marketing_opt_in_level' => null,
        'tax_exemptions' => [],
        'admin_graphql_api_id' => 'gid://shopify/Customer/555666777',
        'default_address' => [
            'id' => 888999000,
            'customer_id' => 555666777,
            'first_name' => 'João',
            'last_name' => 'Silva',
            'company' => null,
            'address1' => 'Rua Teste, 123',
            'address2' => 'Apt 101',
            'city' => 'Araruama',
            'province' => 'Rio de Janeiro',
            'country' => 'Brazil',
            'zip' => '28979-000',
            'phone' => '+5522999999999',
            'name' => 'João Silva',
            'province_code' => 'RJ',
            'country_code' => 'BR',
            'country_name' => 'Brazil',
            'default' => true
        ]
    ]
];

echo "<p><strong>Dados simulados do Shopify:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
echo json_encode($shopifyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";

echo "<h2>🔗 Links Úteis:</h2>";
echo "<ul>";
echo "<li><a href='https://traqueamentophp.onrender.com/events-logs.php' target='_blank'>📊 Ver Logs de Eventos</a></li>";
echo "<li><a href='https://traqueamentophp.onrender.com/webhook/shopify' target='_blank'>🔗 Endpoint do Webhook</a></li>";
echo "</ul>";

echo "<h2>✅ Próximos Passos:</h2>";
echo "<ol>";
echo "<li><strong>Teste no Shopify:</strong> Use a função 'Testar webhook' no painel do Shopify</li>";
echo "<li><strong>Pedido Real:</strong> Faça um pedido de teste na sua loja</li>";
echo "<li><strong>Verificar Logs:</strong> Acesse os logs para ver se os dados chegaram</li>";
echo "<li><strong>Monitorar:</strong> Acompanhe os eventos sendo processados</li>";
echo "</ol>";

echo "<p><em>Teste criado em: " . date('Y-m-d H:i:s') . "</em></p>";
?> 