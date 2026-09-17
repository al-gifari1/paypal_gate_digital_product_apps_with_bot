<?php
use App\Core\Security;
use App\Core\Session;
use App\Services\SettingsService;

$defaultCurrency = SettingsService::get('currency', 'USD');
?>

<div style="display: flex; flex-direction: column; gap: 18px; width: 100%;">
    <!-- Top Action Breadcrumb Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="/admin/products" class="btn-utility" style="padding: 8px 12px;" title="Back to Products Catalog">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="desktop-only">Back to Catalog</span>
            </a>
            <div>
                <h3 style="font-family: var(--font-display); font-size: 20px; font-weight: 600; color: #ffffff; line-height: 1.1;">
                    Create Digital Product
                </h3>
                <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
                    Configure pricing, automated delivery pipeline, and catalog presentation
                </span>
            </div>
        </div>

        <a href="/admin/cards" class="btn-utility" style="font-size: 11px;">
            <i class="fa-solid fa-id-card"></i> Manage Card Pools
        </a>
    </div>

    <!-- Master Product Creator Layout (Form + Real-Time Live Preview) -->
    <form method="POST" action="/admin/products/create" enctype="multipart/form-data" id="productCreateForm">
        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
        <input type="hidden" name="product_type" id="product_type" value="downloadable_file">

        <div class="product-create-layout">
            <!-- LEFT COLUMN: Configurations & Asset Uploads -->
            <div class="product-form-column">
                
                <!-- Section 1: Core Details -->
                <div class="panel-surface">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-tag" style="color: var(--accent-cyan);"></i>
                            <span>General Information</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required 
                               placeholder="e.g. VIP Telegram Access / Python Algorithmic Bot Code" 
                               autocomplete="off">
                        <div class="settings-field-hint">A clear, descriptive title visible across storefront and checkout.</div>
                    </div>

                    <div class="settings-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="price">Price *</label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <span style="position: absolute; left: 12px; font-family: var(--font-mono); font-weight: 700; color: var(--accent-cyan); font-size: 14px; pointer-events: none;">$</span>
                                <input type="number" step="0.01" min="0.01" id="price" name="price" 
                                       class="form-control" style="padding-left: 32px;" required placeholder="19.99">
                            </div>
                            <div class="settings-field-hint">Direct PayPal charge amount upon checkout.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="currency">Store Currency</label>
                            <select name="currency" id="currency" class="form-control">
                                <option value="<?= Security::escape($defaultCurrency) ?>" selected><?= Security::escape($defaultCurrency) ?> (Platform Default)</option>
                                <option value="USD">USD - US Dollar ($)</option>
                                <option value="EUR">EUR - Euro (€)</option>
                                <option value="GBP">GBP - British Pound (£)</option>
                                <option value="CAD">CAD - Canadian Dollar ($)</option>
                                <option value="AUD">AUD - Australian Dollar ($)</option>
                            </select>
                            <div class="settings-field-hint">ISO-4217 standard currency code.</div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 4px;">
                        <label class="form-label" for="description">Product Description & Features</label>
                        <textarea id="description" name="description" class="form-control" rows="4" 
                                  placeholder="Detail features, system requirements, licensing terms, and what customers receive immediately after payment..."></textarea>
                    </div>
                </div>

                <!-- Section 2: Delivery Pipeline & Fulfillment Mechanism -->
                <div class="panel-surface">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-truck-fast" style="color: var(--accent-green);"></i>
                            <span>Fulfillment & Delivery Pipeline</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Select Delivery Method *</label>
                        <div class="delivery-choice-grid">
                            <!-- Option 1: File Download -->
                            <div class="delivery-choice-card active" data-type="downloadable_file">
                                <div class="delivery-choice-icon">
                                    <i class="fa-solid fa-cloud-arrow-down"></i>
                                </div>
                                <div class="delivery-choice-details">
                                    <div class="delivery-choice-title">
                                        <span>Downloadable File</span>
                                        <i class="fa-solid fa-circle-check" style="font-size: 13px; color: var(--accent-cyan);"></i>
                                    </div>
                                    <span class="delivery-choice-desc">
                                        Secure ZIP, PDF, or software binary. Buyer receives encrypted token link.
                                    </span>
                                </div>
                            </div>

                            <!-- Option 2: Card / License Key -->
                            <div class="delivery-choice-card" data-type="card_license">
                                <div class="delivery-choice-icon">
                                    <i class="fa-solid fa-key"></i>
                                </div>
                                <div class="delivery-choice-details">
                                    <div class="delivery-choice-title">
                                        <span>License / Serial Pool</span>
                                        <i class="fa-solid fa-shield-halved" style="font-size: 13px; color: var(--accent-green);"></i>
                                    </div>
                                    <span class="delivery-choice-desc">
                                        Digital accounts, serial numbers, or license keys dispensed line-by-line.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload Container -->
                    <div class="form-group" id="downloadable_asset_section" style="margin-top: 10px;">
                        <label class="form-label" for="product_file">Digital Asset File (Protected Storage)</label>
                        <div class="file-upload-box">
                            <input type="file" id="product_file" name="product_file">
                            <i class="fa-solid fa-file-shield file-upload-icon"></i>
                            <span class="file-upload-label">Choose file or drag & drop here</span>
                            <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
                                Stored securely in zero-trust private directory outside webroot
                            </span>
                            <div class="file-upload-name-badge" id="fileNameBadge"></div>
                        </div>
                    </div>

                    <!-- License Keys Pool Container -->
                    <div class="form-group" id="license_keys_section" style="display: none; margin-top: 10px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label class="form-label" for="initial_keys" style="margin-bottom: 0;">
                                Initial Serial Keys / Cards Inventory
                            </label>
                            <span class="key-counter-pill" id="keyCounterBadge">0 Keys Ready</span>
                        </div>
                        <textarea id="initial_keys" name="initial_keys" class="form-control" rows="6" 
                                  style="font-family: var(--font-mono); font-size: 12px; line-height: 1.6;" 
                                  placeholder="KEY-AAAA-BBBB-1111&#10;KEY-CCCC-DDDD-2222&#10;user:pass:token_serial"></textarea>
                        <div class="settings-field-hint">
                            Enter one key or account credential per line. Each successful checkout will automatically allocate and lock one item.
                        </div>
                    </div>
                </div>

                <!-- Section 3: Visual Cover Media -->
                <div class="panel-surface">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-image" style="color: var(--accent-purple);"></i>
                            <span>Product Cover Media</span>
                        </div>
                    </div>

                    <div class="settings-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="product_image">Upload Cover Image</label>
                            <div class="file-upload-box" style="padding: 14px;">
                                <input type="file" id="product_image" name="product_image" accept="image/*">
                                <i class="fa-solid fa-cloud-arrow-up file-upload-icon" style="color: var(--accent-purple);"></i>
                                <span class="file-upload-label">Select banner image</span>
                                <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
                                    PNG, JPG, WEBP, SVG (Max 5MB)
                                </span>
                                <div class="file-upload-name-badge" id="imageFileNameBadge" style="background:#4c1d95; color:#e9d5ff;"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="image_url">Or Provide Image URL</label>
                            <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://example.com/cover.png">
                            <div class="settings-field-hint" style="margin-top: 8px;">
                                Alternatively paste a direct link to an image hosted on CDN, Imgur, or cloud storage.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Sticky Action Footer -->
                <div class="settings-sticky-footer">
                    <a href="/admin/products" class="btn-utility">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                    <button type="submit" class="btn-utility cyan" style="padding: 10px 24px; font-size: 13px;">
                        <i class="fa-solid fa-check"></i> Save & Publish Product
                    </button>
                </div>

            </div>

            <!-- RIGHT COLUMN: Real-Time Live Preview & Pipeline Matrix -->
            <div class="product-preview-column">
                <div class="panel-surface">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-eye" style="color: var(--accent-cyan);"></i>
                            <span>Live Storefront Preview</span>
                        </div>
                        <span style="font-size: 10px; font-family: var(--font-mono); color: var(--accent-green); font-weight: 700;">
                            • REALTIME
                        </span>
                    </div>

                    <!-- Live Card Mockup matching Storefront -->
                    <div class="preview-product-card">
                        <div class="preview-product-thumb" id="previewThumb">
                            <i class="fa-solid fa-cube preview-placeholder-icon"></i>
                            <span class="preview-product-badge" id="previewProductBadge">DOWNLOADABLE</span>
                        </div>

                        <div class="preview-product-body">
                            <h4 class="preview-product-title" id="previewTitle">Untitled Product</h4>
                            <p class="preview-product-desc" id="previewDesc">Product description will appear here as you type...</p>

                            <div class="preview-product-footer">
                                <div>
                                    <span style="font-size: 10px; font-family: var(--font-mono); color: var(--text-muted); display: block;">PRICE</span>
                                    <span class="preview-product-price" id="previewPrice">$0.00</span>
                                </div>
                                <div class="preview-btn-mock">
                                    <i class="fa-brands fa-paypal"></i> Buy Now
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Fulfillment Checklist -->
                <div class="panel-surface">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-shield-check" style="color: var(--accent-green);"></i>
                            <span>Security & Automation</span>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 12px; color: var(--text-secondary);">
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <i class="fa-solid fa-bolt" style="color: var(--accent-cyan); margin-top: 3px;"></i>
                            <span><strong>Zero Latency:</strong> Immediate PayPal webhook verification & token delivery.</span>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <i class="fa-solid fa-lock" style="color: var(--accent-green); margin-top: 3px;"></i>
                            <span><strong>Zero-Trust Storage:</strong> Files are never exposed via direct public URLs.</span>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <i class="fa-brands fa-telegram" style="color: #38bdf8; margin-top: 3px;"></i>
                            <span><strong>Bot Broadcast:</strong> Instant dispatch to Telegram buyers and admin alerts.</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
