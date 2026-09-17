/**
 * Ninja Radar / Anti-Gravity Admin Interactive Engine
 */
document.addEventListener('DOMContentLoaded', () => {
    // Mobile Drawer Elements
    const sidebar = document.getElementById('radarSidebar');
    const backdrop = document.getElementById('radarBackdrop');
    const menuToggle = document.getElementById('mobileMenuToggle');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const bottomMenuTrigger = document.getElementById('mobileBottomMenuTrigger');

    function openDrawer() {
        if (sidebar && backdrop) {
            sidebar.classList.add('open');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDrawer() {
        if (sidebar && backdrop) {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    if (menuToggle) menuToggle.addEventListener('click', openDrawer);
    if (bottomMenuTrigger) bottomMenuTrigger.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    // Auto-close on Esc key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDrawer();
    });

    // Auto-close on navigation item click (mobile)
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 960) {
                closeDrawer();
            }
        });
    });

    // Telegram Web App SDK Support
    if (window.Telegram && window.Telegram.WebApp) {
        try {
            window.Telegram.WebApp.ready();
            window.Telegram.WebApp.expand();
            window.Telegram.WebApp.headerColor = '#0f172a';
            window.Telegram.WebApp.backgroundColor = '#0b1120';
        } catch (e) {
            console.warn('Telegram WebApp init notice:', e);
        }
    }

    // Live Pulse indicator
    const liveIndicator = document.querySelector('.status-badge-live span');
    if (liveIndicator) {
        setInterval(() => {
            liveIndicator.style.opacity = liveIndicator.style.opacity === '0.5' ? '1' : '0.5';
        }, 1200);
    }

    // Dynamic Filter on Data Tables
    const searchInput = document.getElementById('globalTableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Auto-dismiss Flash Alerts
    const alerts = document.querySelectorAll('.flash-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Anti-Gravity V3 Universal Custom Dropdown Enhancer
    initCustomSelects();

    // Anti-Gravity V3 Settings Hub Engine
    initSettingsHub();

    // Anti-Gravity V3 Product Creator Engine
    initProductCreator();
});

function initSettingsHub() {
    const tabBtns = document.querySelectorAll('.settings-tab-btn');
    const tabPanes = document.querySelectorAll('.settings-tab-pane');

    if (tabBtns.length > 0) {
        function switchTab(targetId) {
            tabBtns.forEach(btn => {
                const isActive = btn.dataset.tab === targetId;
                btn.classList.toggle('active', isActive);
            });
            tabPanes.forEach(pane => {
                const isMatch = pane.id === targetId;
                pane.classList.toggle('active', isMatch);
            });
        }

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.tab;
                switchTab(target);
                history.replaceState(null, '', '#' + target);
            });
        });

        // Initialize from URL Hash if present
        if (window.location.hash) {
            const hashTarget = window.location.hash.substring(1);
            if (document.getElementById(hashTarget)) {
                switchTab(hashTarget);
            }
        }
    }

    // Password / Secret Visibility Toggles
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetInputId = btn.dataset.target;
            const input = document.getElementById(targetInputId);
            if (!input) return;

            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });

    // 1-Click Clipboard Copy Buttons
    document.querySelectorAll('.btn-copy-code').forEach(btn => {
        btn.addEventListener('click', () => {
            const textToCopy = btn.dataset.copy || '';
            if (!textToCopy) return;

            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('Clipboard copy failed:', err);
            });
        });
    });
}

function initCustomSelects() {
    document.querySelectorAll('select.form-control').forEach(select => {
        if (select.dataset.customized === 'true') return;
        select.dataset.customized = 'true';
        select.style.display = 'none';

        const isTableFilter = select.classList.contains('table-filter-select');
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper' + (isTableFilter ? ' table-filter-select-wrapper' : '');

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';

        const label = document.createElement('span');
        label.className = 'select-label';
        const selectedOpt = select.options[select.selectedIndex] || select.options[0];
        label.textContent = selectedOpt ? selectedOpt.text : '-- Select --';

        const chevron = document.createElement('i');
        chevron.className = 'fa-solid fa-chevron-down select-chevron';

        trigger.appendChild(label);
        trigger.appendChild(chevron);

        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';

        Array.from(select.options).forEach((opt, idx) => {
            const item = document.createElement('div');
            item.className = 'custom-select-option' + (idx === select.selectedIndex ? ' selected' : '');
            item.textContent = opt.text;
            item.dataset.value = opt.value;

            item.addEventListener('click', (e) => {
                e.stopPropagation();
                select.selectedIndex = idx;
                label.textContent = opt.text;
                menu.querySelectorAll('.custom-select-option').forEach(o => o.classList.remove('selected'));
                item.classList.add('selected');
                wrapper.classList.remove('open');

                // Dispatch change event on original select for onchange callbacks
                const event = new Event('change', { bubbles: true });
                select.dispatchEvent(event);
            });

            menu.appendChild(item);
        });

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = wrapper.classList.contains('open');
            document.querySelectorAll('.custom-select-wrapper.open').forEach(w => w.classList.remove('open'));
            if (!isOpen) {
                wrapper.classList.add('open');
            }
        });

        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);
        select.parentNode.insertBefore(wrapper, select.nextSibling);
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-select-wrapper.open').forEach(w => w.classList.remove('open'));
    });
}

function confirmAction(message, formId) {
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
}

function initProductCreator() {
    const layout = document.querySelector('.product-create-layout');
    if (!layout) return;

    const typeInput = document.getElementById('product_type');
    const choiceCards = document.querySelectorAll('.delivery-choice-card');
    const fileSection = document.getElementById('downloadable_asset_section');
    const keysSection = document.getElementById('license_keys_section');
    const previewBadge = document.getElementById('previewProductBadge');

    // Delivery Choice Toggle
    choiceCards.forEach(card => {
        card.addEventListener('click', () => {
            const type = card.dataset.type;
            choiceCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');

            if (typeInput) typeInput.value = type;

            if (type === 'card_license') {
                if (fileSection) fileSection.style.display = 'none';
                if (keysSection) keysSection.style.display = 'flex';
                if (previewBadge) previewBadge.textContent = 'LICENSE KEY';
            } else {
                if (fileSection) fileSection.style.display = 'flex';
                if (keysSection) keysSection.style.display = 'none';
                if (previewBadge) previewBadge.textContent = 'DOWNLOADABLE';
            }
        });
    });

    // Real-Time Preview Elements
    const nameInput = document.getElementById('name');
    const priceInput = document.getElementById('price');
    const descInput = document.getElementById('description');
    const previewTitle = document.getElementById('previewTitle');
    const previewPrice = document.getElementById('previewPrice');
    const previewDesc = document.getElementById('previewDesc');
    const previewThumb = document.getElementById('previewThumb');

    if (nameInput && previewTitle) {
        nameInput.addEventListener('input', () => {
            previewTitle.textContent = nameInput.value.trim() || 'Untitled Product';
        });
    }

    const currencySelect = document.getElementById('currency');
    function updatePreviewPrice() {
        if (!previewPrice) return;
        const val = parseFloat(priceInput ? priceInput.value : 0);
        const curr = currencySelect ? currencySelect.value : 'USD';
        const formatted = isNaN(val) ? '0.00' : val.toFixed(2);
        previewPrice.textContent = `$${formatted} ${curr}`;
    }

    if (priceInput) priceInput.addEventListener('input', updatePreviewPrice);
    if (currencySelect) currencySelect.addEventListener('change', updatePreviewPrice);

    if (descInput && previewDesc) {
        descInput.addEventListener('input', () => {
            previewDesc.textContent = descInput.value.trim() || 'Product description will appear here...';
        });
    }

    // Cover Image Preview (File Upload & URL)
    const imageFileInput = document.getElementById('product_image');
    const imageUrlInput = document.getElementById('image_url');

    function updatePreviewImage(src) {
        if (!previewThumb) return;
        if (src) {
            previewThumb.innerHTML = `<img src="${src}" alt="Cover Preview" style="width:100%;height:100%;object-fit:cover;">`;
            if (previewBadge) previewThumb.appendChild(previewBadge);
        } else {
            previewThumb.innerHTML = `<i class="fa-solid fa-cube preview-placeholder-icon"></i>`;
            if (previewBadge) previewThumb.appendChild(previewBadge);
        }
    }

    if (imageFileInput) {
        imageFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => updatePreviewImage(event.target.result);
                reader.readAsDataURL(file);

                const badge = document.getElementById('imageFileNameBadge');
                if (badge) {
                    badge.innerHTML = `<i class="fa-solid fa-image"></i> ${file.name} (${(file.size / 1024).toFixed(0)} KB)`;
                    badge.style.display = 'inline-flex';
                }
            }
        });
    }

    if (imageUrlInput) {
        imageUrlInput.addEventListener('input', () => {
            const url = imageUrlInput.value.trim();
            if (url) updatePreviewImage(url);
        });
    }

    // Download Asset File Badge
    const productFileInput = document.getElementById('product_file');
    const fileNameBadge = document.getElementById('fileNameBadge');
    if (productFileInput && fileNameBadge) {
        productFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                fileNameBadge.innerHTML = `<i class="fa-solid fa-file-zipper"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                fileNameBadge.style.display = 'inline-flex';
            }
        });
    }

    // License Keys Real-Time Counter
    const keysTextarea = document.getElementById('initial_keys');
    const keyCounter = document.getElementById('keyCounterBadge');
    if (keysTextarea && keyCounter) {
        keysTextarea.addEventListener('input', () => {
            const lines = keysTextarea.value.split('\n').filter(line => line.trim().length > 0);
            const count = lines.length;
            keyCounter.textContent = `${count} ${count === 1 ? 'Key' : 'Keys'} Ready`;
        });
    }
}

