<?php
use App\Core\Security;
use App\Core\Session;

$openApiUrl = rtrim($appUrl, '/') . '/api/v1/openapi.json';
$apiBaseUrl = rtrim($appUrl, '/') . '/api/v1';
?>

<div class="settings-container">
    <!-- Top Credential & Telemetry Bar -->
    <div class="panel-surface">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-key" style="color: var(--accent-cyan);"></i>
                <span>Master API Secret Key & Authentication</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <a href="/api/v1/openapi.json" target="_blank" class="btn-utility cyan" style="font-size: 11px;">
                    <i class="fa-solid fa-file-code"></i> OpenAPI 3.1 Spec
                </a>
            </div>
        </div>

        <div class="settings-form-grid">
            <div class="form-group">
                <label class="form-label">Master API Token (Keep Secret)</label>
                <div class="input-action-wrapper">
                    <input type="password" id="masterApiKeyInput" class="form-control" 
                           value="<?= Security::escape($apiKey) ?>" readonly spellcheck="false">
                    <button type="button" class="input-action-btn password-toggle-btn" data-target="masterApiKeyInput" title="Toggle Visibility">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button type="button" class="input-action-btn btn-copy-code" data-copy="<?= Security::escape($apiKey) ?>" title="Copy Key" style="right: 36px;">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
                <div class="settings-field-hint">
                    Send via HTTP header: <code>X-API-Key: <?= substr($apiKey, 0, 10) ?>...</code> or <code>Authorization: Bearer <?= substr($apiKey, 0, 10) ?>...</code>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">API Root Base Endpoint</label>
                <div class="settings-code-box" style="margin-top: 0; min-height: 42px;">
                    <span class="settings-code-text"><?= Security::escape($apiBaseUrl) ?></span>
                    <button type="button" class="btn-copy-code" data-copy="<?= Security::escape($apiBaseUrl) ?>">
                        <i class="fa-solid fa-copy"></i> Copy
                    </button>
                </div>
                <div class="settings-field-hint">
                    All v1 endpoints are mounted under this prefix.
                </div>
            </div>
        </div>

        <!-- Regenerate Key Form -->
        <div style="margin-top: 14px; border-top: 1px solid var(--hairline); padding-top: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
                <i class="fa-solid fa-triangle-exclamation" style="color: var(--accent-amber);"></i>
                Regenerating will immediately revoke the current key for all running bots and AI agents.
            </span>
            <form method="POST" action="/admin/api/regenerate" onsubmit="return confirm('Revoke current API key and issue a new one? All external integrations must be updated!');">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                <button type="submit" class="btn-utility danger" style="font-size: 11px;">
                    <i class="fa-solid fa-rotate"></i> Rotate / Regenerate Key
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="settings-tab-nav" role="tablist">
        <button type="button" class="settings-tab-btn active" data-tab="tab-api-tester">
            <i class="fa-solid fa-terminal"></i>
            <span>Interactive API Tester</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="tab-llm-tools">
            <i class="fa-solid fa-robot"></i>
            <span>LLM & Custom GPTs Setup</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="tab-curl-docs">
            <i class="fa-solid fa-code"></i>
            <span>cURL & Code Samples</span>
        </button>
    </div>

    <!-- TAB 1: Interactive API Tester -->
    <div class="settings-tab-pane active" id="tab-api-tester">
        <div class="panel-surface">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-bolt" style="color: var(--accent-green);"></i>
                    <span>Live Console Request Runner</span>
                </div>
                <span style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">
                    Uses your active Master Key
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <!-- Endpoint Selector Row -->
                <div style="display: grid; grid-template-columns: 110px 1fr 120px; gap: 10px;">
                    <div>
                        <select id="apiTesterMethod" class="form-control" style="font-family: var(--font-mono); font-weight: 700; color: var(--accent-cyan);">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>

                    <div style="position: relative;">
                        <input type="text" id="apiTesterEndpoint" class="form-control" 
                               value="/api/v1/stats" 
                               style="font-family: var(--font-mono); font-size: 13px;" 
                               placeholder="/api/v1/products">
                    </div>

                    <button type="button" id="btnSendApiRequest" class="btn-utility cyan" style="justify-content: center;">
                        <i class="fa-solid fa-paper-plane"></i> Send
                    </button>
                </div>

                <!-- Quick Presets -->
                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                    <span style="font-size: 10px; font-family: var(--font-mono); color: var(--text-muted); text-transform: uppercase;">Quick Presets:</span>
                    <button type="button" class="btn-preset btn-utility" data-method="GET" data-path="/api/v1/stats" data-body="" style="padding: 3px 8px; font-size: 10px;">
                        GET Stats
                    </button>
                    <button type="button" class="btn-preset btn-utility" data-method="GET" data-path="/api/v1/products" data-body="" style="padding: 3px 8px; font-size: 10px;">
                        GET Products
                    </button>
                    <button type="button" class="btn-preset btn-utility" data-method="POST" data-path="/api/v1/products" 
                            data-body='{"name": "AI Generated VIP Bundle", "price": 24.99, "currency": "USD", "description": "High performance algorithmic bot with instant license delivery.", "product_type": "card_license", "initial_keys": ["KEY-VIP-8888-1111", "KEY-VIP-8888-2222"]}' 
                            style="padding: 3px 8px; font-size: 10px;">
                        POST Product (AI Create)
                    </button>
                    <button type="button" class="btn-preset btn-utility" data-method="GET" data-path="/api/v1/orders" data-body="" style="padding: 3px 8px; font-size: 10px;">
                        GET Orders
                    </button>
                </div>

                <!-- JSON Request Body (For POST/PUT) -->
                <div id="requestBodyContainer" style="display: none; flex-direction: column; gap: 6px;">
                    <label class="form-label">JSON Request Body</label>
                    <textarea id="apiTesterBody" class="form-control" rows="5" 
                              style="font-family: var(--font-mono); font-size: 12px; line-height: 1.5; color: #38bdf8;" 
                              placeholder='{\n  "name": "Product Name",\n  "price": 19.99\n}'></textarea>
                </div>

                <!-- Live Response Section -->
                <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span class="form-label" style="margin-bottom: 0;">API Server Response</span>
                        <div style="display: flex; align-items: center; gap: 10px; font-family: var(--font-mono); font-size: 11px;">
                            <span id="responseStatusPill" class="mobile-only-pill" style="display: none;"></span>
                            <span id="responseLatency" style="color: var(--text-muted);"></span>
                        </div>
                    </div>

                    <div style="background-color: var(--canvas-bg); border-radius: var(--radius-btn); padding: 14px; position: relative; box-shadow: inset 0 2px 8px rgba(0,0,0,0.5);">
                        <button type="button" id="btnCopyResponse" class="btn-copy-code" style="position: absolute; top: 10px; right: 10px;" title="Copy JSON">
                            <i class="fa-solid fa-copy"></i> Copy JSON
                        </button>
                        <pre id="apiTesterResponse" style="margin: 0; font-family: var(--font-mono); font-size: 12px; line-height: 1.6; color: #a5f3fc; overflow-x: auto; max-height: 380px;">// Click 'Send' above to dispatch a live request using your Master API key...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: LLM & AI Agents Integration -->
    <div class="settings-tab-pane" id="tab-llm-tools">
        <div class="panel-surface">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-robot" style="color: var(--accent-cyan);"></i>
                    <span>Connect ChatGPT, Claude & Autonomous Agents</span>
                </div>
            </div>

            <p style="font-size: 12px; color: var(--text-secondary); line-height: 1.6;">
                Use these ready-to-copy configurations to equip your AI agents with real-world tool execution abilities to create products, manage card inventory, and audit revenue.
            </p>

            <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 14px;">
                <!-- Step 1: Custom GPT Action (OpenAPI) -->
                <div class="diag-action-card">
                    <h5><i class="fa-solid fa-wand-magic-sparkles" style="color: #38bdf8;"></i> Option 1: OpenAI Custom GPT / Actions</h5>
                    <p>
                        In your ChatGPT Custom GPT editor under <strong>Actions > Create New Action</strong>, paste this live OpenAPI Schema URL:
                    </p>
                    <div class="settings-code-box">
                        <span class="settings-code-text"><?= Security::escape($openApiUrl) ?></span>
                        <button type="button" class="btn-copy-code" data-copy="<?= Security::escape($openApiUrl) ?>">
                            <i class="fa-solid fa-copy"></i> Copy URL
                        </button>
                    </div>
                    <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); margin-top: 4px;">
                        Authentication: Select <strong>API Key</strong>, Header Name: <code>X-API-Key</code>, Value: Paste your Master Key.
                    </span>
                </div>

                <!-- Step 2: System Prompt Recommendation -->
                <div class="diag-action-card">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <h5><i class="fa-solid fa-brain" style="color: var(--accent-purple);"></i> Option 2: AI Agent System Prompt Directive</h5>
                        <button type="button" class="btn-copy-code" data-copy="You are an autonomous Store Operations Agent for our digital e-commerce platform. When instructed to add products, use the 'createProduct' tool. Ensure descriptions are persuasive, formatting is clean, and initial license keys are passed in the 'initial_keys' array. Always verify product pricing (>0.01) and currency (USD).">
                            <i class="fa-solid fa-copy"></i> Copy Prompt
                        </button>
                    </div>
                    <pre style="background: var(--canvas-bg); padding: 12px; border-radius: var(--radius-btn); font-family: var(--font-mono); font-size: 11px; color: #cbd5e1; line-height: 1.5; white-space: pre-wrap; margin-top: 6px;">You are an autonomous Store Operations Agent for our digital e-commerce platform. When instructed to add products, use the 'createProduct' tool. Ensure descriptions are persuasive, formatting is clean, and initial license keys are passed in the 'initial_keys' array. Always verify product pricing (>0.01) and currency (USD).</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: cURL & Code Samples -->
    <div class="settings-tab-pane" id="tab-curl-docs">
        <div class="panel-surface">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-terminal" style="color: var(--accent-green);"></i>
                    <span>Ready-to-Run cURL Automation Commands</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 18px;">
                <!-- 1. Create Product -->
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span class="form-label" style="margin-bottom: 0;">1. Create Product with License Key Pool (POST)</span>
                        <button type="button" class="btn-copy-code" data-copy='curl -X POST "<?= $apiBaseUrl ?>/products" \
  -H "X-API-Key: <?= $apiKey ?>" \
  -H "Content-Type: application/json" \
  -d \x27{
    "name": "Python Algo Trading Script",
    "price": 29.99,
    "currency": "USD",
    "description": "Instant delivery serial key included.",
    "product_type": "card_license",
    "initial_keys": ["SERIAL-1111-2222", "SERIAL-3333-4444"]
  }\x27'>
                            <i class="fa-solid fa-copy"></i> Copy cURL
                        </button>
                    </div>
                    <pre style="background: var(--canvas-bg); padding: 12px; border-radius: var(--radius-btn); font-family: var(--font-mono); font-size: 11px; color: #38bdf8; overflow-x: auto; line-height: 1.5;">curl -X POST "<?= $apiBaseUrl ?>/products" \
  -H "X-API-Key: <?= $apiKey ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Python Algo Trading Script",
    "price": 29.99,
    "currency": "USD",
    "description": "Instant delivery serial key included.",
    "product_type": "card_license",
    "initial_keys": ["SERIAL-1111-2222", "SERIAL-3333-4444"]
  }'</pre>
                </div>

                <!-- 2. Bulk Append Keys -->
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span class="form-label" style="margin-bottom: 0;">2. Bulk Append License Keys to Product (POST)</span>
                        <button type="button" class="btn-copy-code" data-copy='curl -X POST "<?= $apiBaseUrl ?>/products/1/cards" \
  -H "X-API-Key: <?= $apiKey ?>" \
  -H "Content-Type: application/json" \
  -d \x27{"keys": ["KEY-AAAA-BBBB", "KEY-CCCC-DDDD"]}\x27'>
                            <i class="fa-solid fa-copy"></i> Copy cURL
                        </button>
                    </div>
                    <pre style="background: var(--canvas-bg); padding: 12px; border-radius: var(--radius-btn); font-family: var(--font-mono); font-size: 11px; color: #4ade80; overflow-x: auto; line-height: 1.5;">curl -X POST "<?= $apiBaseUrl ?>/products/1/cards" \
  -H "X-API-Key: <?= $apiKey ?>" \
  -H "Content-Type: application/json" \
  -d '{"keys": ["KEY-AAAA-BBBB", "KEY-CCCC-DDDD"]}'</pre>
                </div>

                <!-- 3. Get Stats -->
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span class="form-label" style="margin-bottom: 0;">3. Query Store Telemetry & Low Stock (GET)</span>
                        <button type="button" class="btn-copy-code" data-copy='curl -X GET "<?= $apiBaseUrl ?>/stats" -H "X-API-Key: <?= $apiKey ?>"'>
                            <i class="fa-solid fa-copy"></i> Copy cURL
                        </button>
                    </div>
                    <pre style="background: var(--canvas-bg); padding: 12px; border-radius: var(--radius-btn); font-family: var(--font-mono); font-size: 11px; color: #facc15; overflow-x: auto; line-height: 1.5;">curl -X GET "<?= $apiBaseUrl ?>/stats" \
  -H "X-API-Key: <?= $apiKey ?>"</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const methodSelect = document.getElementById('apiTesterMethod');
    const endpointInput = document.getElementById('apiTesterEndpoint');
    const bodyContainer = document.getElementById('requestBodyContainer');
    const bodyTextarea = document.getElementById('apiTesterBody');
    const sendBtn = document.getElementById('btnSendApiRequest');
    const responseBox = document.getElementById('apiTesterResponse');
    const statusPill = document.getElementById('responseStatusPill');
    const latencySpan = document.getElementById('responseLatency');
    const copyResponseBtn = document.getElementById('btnCopyResponse');

    const apiKey = '<?= Security::escape($apiKey) ?>';

    function checkMethod() {
        const m = methodSelect.value;
        if (m === 'POST' || m === 'PUT') {
            bodyContainer.style.display = 'flex';
        } else {
            bodyContainer.style.display = 'none';
        }
    }

    if (methodSelect) {
        methodSelect.addEventListener('change', checkMethod);
        checkMethod();
    }

    // Quick Presets
    document.querySelectorAll('.btn-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            methodSelect.value = btn.dataset.method;
            endpointInput.value = btn.dataset.path;
            bodyTextarea.value = btn.dataset.body || '';
            checkMethod();
        });
    });

    // Send Live Request
    if (sendBtn) {
        sendBtn.addEventListener('click', async () => {
            const method = methodSelect.value;
            const endpoint = endpointInput.value.trim();
            const bodyStr = bodyTextarea.value.trim();

            if (!endpoint) {
                alert('Please provide an endpoint path.');
                return;
            }

            responseBox.textContent = '// Sending request to ' + endpoint + '...';
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            const startTime = performance.now();

            try {
                const options = {
                    method: method,
                    headers: {
                        'X-API-Key': apiKey,
                        'Accept': 'application/json',
                    }
                };

                if ((method === 'POST' || method === 'PUT') && bodyStr) {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = bodyStr;
                }

                const res = await fetch(endpoint, options);
                const latency = Math.round(performance.now() - startTime);

                const data = await res.json();
                responseBox.textContent = JSON.stringify(data, null, 2);

                statusPill.style.display = 'inline-block';
                statusPill.textContent = res.status + ' ' + (res.ok ? 'OK' : 'ERROR');
                statusPill.className = 'mobile-only-pill ' + (res.ok ? 'live' : 'sandbox');

                latencySpan.textContent = latency + ' ms';

            } catch (err) {
                const latency = Math.round(performance.now() - startTime);
                responseBox.textContent = '// Network/Parse Error:\n' + err.message;
                statusPill.style.display = 'inline-block';
                statusPill.textContent = 'ERR';
                statusPill.className = 'mobile-only-pill sandbox';
                latencySpan.textContent = latency + ' ms';
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send';
            }
        });
    }

    if (copyResponseBtn) {
        copyResponseBtn.addEventListener('click', () => {
            const text = responseBox.textContent;
            navigator.clipboard.writeText(text).then(() => {
                copyResponseBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                setTimeout(() => copyResponseBtn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy JSON', 2000);
            });
        });
    }
});
</script>
