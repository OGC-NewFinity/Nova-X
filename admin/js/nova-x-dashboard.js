/**
 * Nova-X Dashboard JavaScript
 * 
 * @package Nova-X
 */

(function ($) {
    'use strict';

    /**
     * Dashboard functionality
     */
    const NovaXDashboard = {
        /**
         * Initialize
         */
        init: function () {
            this.initSidebarNavigation();
            this.handleTabSwitching();
            this.handleGenerateTheme();
            this.handleCopyOutput();
            this.handleExportOutput();
            this.handleResetTracker();
            this.handlePreviewLoading();
            this.handleCustomizeOutput();
            this.handleUsageStats();
            this.handleExportedThemes();
            this.initHeaderControls();
            this.initThemeToggle();
            this.initHeaderOverlay();
            this.initNotices();
        },

        /**
         * Initialize sidebar navigation
         */
        initSidebarNavigation: function () {
            const $sidebar = $('#nova-x-sidebar');
            const $sidebarLinks = $('.nova-x-sidebar-link');
            const $toggleBtn = $('#nova-x-sidebar-toggle');
            const $topNavTabs = $('.nova-x-top-nav .nav-tab');

            if (!$sidebar.length) {
                return; // Sidebar not present
            }

            // Handle sidebar link clicks
            $sidebarLinks.on('click', function(e) {
                e.preventDefault();
                
                const $link = $(this);
                const tab = $link.data('tab');
                
                if (!tab) {
                    return;
                }

                // Update active states
                $sidebarLinks.removeClass('active');
                $link.addClass('active');
                $topNavTabs.removeClass('nav-tab-active');
                $topNavTabs.filter('[data-tab="' + tab + '"]').addClass('nav-tab-active');

                // Switch tab content without page reload
                NovaXDashboard.switchTab(tab);
            });

            // Handle sidebar toggle (collapse/expand)
            $toggleBtn.on('click', function() {
                $sidebar.toggleClass('collapsed');
                const isCollapsed = $sidebar.hasClass('collapsed');
                
                // Update toggle icon
                const $icon = $toggleBtn.find('.dashicons');
                if (isCollapsed) {
                    $icon.removeClass('dashicons-arrow-left-alt2').addClass('dashicons-arrow-right-alt2');
                } else {
                    $icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-left-alt2');
                }

                // Store state in localStorage
                localStorage.setItem('nova_x_sidebar_collapsed', isCollapsed ? '1' : '0');
            });

            // Restore sidebar state from localStorage
            const savedState = localStorage.getItem('nova_x_sidebar_collapsed');
            if (savedState === '1') {
                $sidebar.addClass('collapsed');
                $toggleBtn.find('.dashicons').removeClass('dashicons-arrow-left-alt2').addClass('dashicons-arrow-right-alt2');
            }

            // Sync with top nav tabs (if clicked)
            $topNavTabs.on('click', function(e) {
                const $tab = $(this);
                const tab = $tab.data('tab');
                
                if (tab) {
                    // Update sidebar active state
                    $sidebarLinks.removeClass('active');
                    $sidebarLinks.filter('[data-tab="' + tab + '"]').addClass('active');
                }
            });

            // Set initial active state based on URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentTab = urlParams.get('tab') || 'generate';
            $sidebarLinks.removeClass('active');
            $sidebarLinks.filter('[data-tab="' + currentTab + '"]').addClass('active');
        },

        /**
         * Switch tab content without page reload
         */
        switchTab: function (tab) {
            const $content = $('#nova-x-tab-content');
            // Get current URL and update tab parameter
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', tab);
            const newUrl = currentUrl.toString();

            // Update URL without reload
            if (history.pushState) {
                history.pushState({ tab: tab }, '', newUrl);
            }

            // Show loading state
            $content.addClass('nova-x-loading');

            // Show/hide panel wrappers
            const $panelWrappers = $content.find('.nova-x-tab-panel-wrapper');
            $panelWrappers.hide();

            const $targetPanel = $content.find('.nova-x-tab-panel-wrapper[data-tab="' + tab + '"]');
            
            if ($targetPanel.length) {
                $targetPanel.show();
                $content.removeClass('nova-x-loading');
                
                // Trigger tab-specific initialization based on tab
                if (tab === 'usage') {
                    setTimeout(function() {
                        NovaXDashboard.loadUsageStats();
                    }, 100);
                } else if (tab === 'exported') {
                    setTimeout(function() {
                        NovaXDashboard.loadExportedThemes();
                    }, 100);
                } else if (tab === 'preview') {
                    // Check preview availability
                    setTimeout(function() {
                        const previewUrl = sessionStorage.getItem('nova_x_preview_url');
                        if (previewUrl) {
                            $(document).trigger('nova-x-preview-ready', [previewUrl]);
                        }
                    }, 100);
                }
                
                $(document).trigger('nova-x-tab-switched', [tab]);
            } else {
                // If panel doesn't exist, reload page to get it
                window.location.href = newUrl;
            }

            // Scroll to top
            $('html, body').animate({
                scrollTop: $content.offset().top - 32
            }, 300);
        },

        /**
         * Handle tab switching (enhanced with smooth transitions)
         */
        handleTabSwitching: function () {
            const $tabs = $('.nova-x-tab-wrapper .nav-tab');
            
            $tabs.on('click', function (e) {
                // Remove active class from all tabs
                $tabs.removeClass('nav-tab-active');
                // Add active class to clicked tab
                $(this).addClass('nav-tab-active');
                
                // Smooth scroll to top of content
                $('html, body').animate({
                    scrollTop: $('.nova-x-tab-content').offset().top - 32
                }, 300);
            });

            // Handle initial tab state based on URL
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                const $activeTab = $('.nova-x-tab-wrapper .nav-tab').filter(function () {
                    return $(this).attr('href').includes('tab=' + tab);
                });
                if ($activeTab.length) {
                    $tabs.removeClass('nav-tab-active');
                    $activeTab.addClass('nav-tab-active');
                }
            }
        },

        /**
         * Handle theme generation form submission
         */
        handleGenerateTheme: function () {
            const $form = $('#nova-x-generate-form');
            const $button = $('#nova-x-generate-btn');
            const $status = $('#nova-x-generate-status');
            const $loader = $('#theme-loader');
            const $titleInput = $('#nova-x-site-title');
            const $promptInput = $('#nova-x-theme-prompt');
            const $providerSelect = $('#nova-x-provider-select');
            const $outputContainer = $('#nova-x-theme-output');
            const $outputCode = $('#nova-x-theme-code');
            const $exportBtn = $('#nova-x-export-theme');
            const $previewBtn = $('#nova-x-preview-theme');
            const $installBtn = $('#nova-x-install-theme');
            const $actionStatus = $('#nova-x-action-status');

            if (!$form.length) {
                return;
            }

            $form.on('submit', function (e) {
                e.preventDefault();

                const title = $titleInput.val().trim();
                const prompt = $promptInput.val().trim();
                const provider = $providerSelect.val();

                // Validation
                if (!title || !prompt) {
                    NovaXDashboard.showStatus($status, '⚠️ Please fill in all required fields.', 'error');
                    return;
                }

                // Hide previous output
                $outputContainer.addClass('nova-x-hidden');
                $exportBtn.prop('disabled', true);
                $previewBtn.prop('disabled', true);
                $installBtn.prop('disabled', true);

                // Disable button and show loading
                $button.prop('disabled', true);
                $loader.removeClass('nova-x-hidden');
                NovaXDashboard.showStatus($status, '⏳ Generating theme...', 'loading');

                // AJAX request
                $.ajax({
                    method: 'POST',
                    url: novaXDashboard.generateThemeUrl,
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                    },
                    data: JSON.stringify({
                        title: title,
                        prompt: prompt,
                        provider: provider,
                        nonce: novaXDashboard.generateNonce || '',
                    }),
                    timeout: 60000,
                })
                    .done(function (res) {
                        if (res.success) {
                            NovaXDashboard.showStatus($status, '✅ Theme generated successfully!', 'success');
                            
                            // If output is provided, display it
                            if (res.output) {
                                $outputCode.text(res.output);
                                $outputContainer.removeClass('nova-x-hidden');
                                
                                // Enable action buttons
                                $exportBtn.prop('disabled', false);
                                $previewBtn.prop('disabled', false);
                                $installBtn.prop('disabled', false);
                                
                                // Store output data for export/preview/install
                                $outputContainer.data('theme-code', res.output);
                                $outputContainer.data('theme-title', title);
                                
                                // Store code in sessionStorage for Customize tab
                                // Parse the output to extract different file types (simplified parsing)
                                const codeOutput = res.output;
                                const codeData = {
                                    style: codeOutput, // In real implementation, parse actual files
                                    functions: '', // Will be parsed from actual theme structure
                                    index: '', // Will be parsed from actual theme structure
                                    updated: false
                                };
                                
                                // Try to extract style.css if present
                                const styleMatch = codeOutput.match(/\/\*\s*Theme Name.*?\*\/([\s\S]*?)(?:\/\*|$)/);
                                if (styleMatch) {
                                    codeData.style = styleMatch[0];
                                }
                                
                                sessionStorage.setItem('nova_x_generated_code', JSON.stringify(codeData));
                                
                                // If on customize tab, trigger a reload of the customize code
                                if (window.location.search.includes('tab=customize')) {
                                    $(document).trigger('nova-x-code-updated');
                                }
                            }
                            
                            // Clear prompt but keep title
                            $promptInput.val('');
                        } else {
                            NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Theme generation failed'), 'error');
                        }
                    })
                    .fail(function (xhr) {
                        let errorMessage = 'Request failed. Please try again.';
                        
                        if (xhr.status === 0) {
                            errorMessage = 'Network error. Please check your connection.';
                            console.warn('Nova-X: AJAX request failed - Network error. Theme generation may have incomplete file content.');
                        } else if (xhr.status === 403) {
                            errorMessage = 'Permission denied. Please refresh the page.';
                            console.warn('Nova-X: AJAX request failed - Permission denied. Check user capabilities.');
                        } else if (xhr.status === 404) {
                            errorMessage = 'API endpoint not found.';
                            console.warn('Nova-X: AJAX request failed - API endpoint not found. Check REST API routes.');
                        } else if (xhr.status >= 500) {
                            errorMessage = 'Server error. Please try again later.';
                            console.warn('Nova-X: AJAX request failed - Server error. Theme files may not have been generated properly.');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                            console.warn('Nova-X: AJAX request failed -', errorMessage);
                        } else {
                            console.warn('Nova-X: AJAX request failed with status', xhr.status, '. Theme file content may be incomplete.');
                        }
                        
                        NovaXDashboard.showStatus($status, '❌ ' + errorMessage, 'error');
                    })
                    .always(function () {
                        $button.prop('disabled', false);
                        $loader.addClass('nova-x-hidden');
                    });
            });

            // Handle export button
            $exportBtn.on('click', function () {
                const themeCode = $outputContainer.data('theme-code');
                if (!themeCode) {
                    NovaXDashboard.showStatus($actionStatus, '❌ No theme code available.', 'error');
                    return;
                }

                // TODO: Implement export functionality via REST API
                NovaXDashboard.showStatus($actionStatus, '⏳ Exporting theme...', 'loading');
                // Placeholder for export functionality
                NovaXDashboard.showStatus($actionStatus, '✅ Export feature coming soon.', 'success');
            });

            // Handle preview button
            $previewBtn.on('click', function () {
                const themeCode = $outputContainer.data('theme-code');
                if (!themeCode) {
                    NovaXDashboard.showStatus($actionStatus, '❌ No theme code available. Please generate a theme first.', 'error');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                NovaXDashboard.showStatus($actionStatus, '⏳ Preparing preview...', 'loading');
                
                // Call preview REST API endpoint
                $.ajax({
                    method: 'POST',
                    url: (typeof novaXDashboard !== 'undefined' && novaXDashboard.previewThemeUrl) 
                        ? novaXDashboard.previewThemeUrl 
                        : novaXDashboard.restUrl + 'preview-theme',
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                    },
                    data: JSON.stringify({
                        zip_url: themeCode,
                        nonce: novaXDashboard.generateNonce || '',
                    }),
                    timeout: 60000,
                })
                .done(function(res) {
                    if (res.success && res.preview_url) {
                        sessionStorage.setItem('nova_x_preview_url', res.preview_url);
                        $(document).trigger('nova-x-preview-ready', [res.preview_url]);
                        NovaXDashboard.showStatus($actionStatus, '✅ Preview ready! Switch to Live Preview tab to view.', 'success');
                    } else {
                        NovaXDashboard.showStatus($actionStatus, '❌ ' + (res.message || 'Preview preparation failed.'), 'error');
                    }
                })
                .fail(function(xhr) {
                    let errorMessage = 'Preview request failed.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    NovaXDashboard.showStatus($actionStatus, '❌ ' + errorMessage, 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false);
                });
            });

            // Handle install button
            $installBtn.on('click', function () {
                if (!confirm('Are you sure you want to install this theme? This will activate it immediately.')) {
                    return;
                }

                NovaXDashboard.showStatus($actionStatus, '⏳ Installing theme...', 'loading');
                // TODO: Implement install functionality
                NovaXDashboard.showStatus($actionStatus, '✅ Install feature coming soon.', 'success');
            });
        },

        /**
         * Handle copy to clipboard
         */
        handleCopyOutput: function () {
            const $button = $('#nova_x_copy_output');
            const $output = $('#nova_x_customize_output');

            if (!$button.length) {
                return;
            }

            $button.on('click', function () {
                if (!$output.val()) {
                    alert('No content to copy.');
                    return;
                }

                $output.select();
                document.execCommand('copy');

                // Visual feedback
                const originalText = $button.text();
                $button.text('✓ Copied!');
                $button.addClass('button-secondary').removeClass('button');

                setTimeout(function () {
                    $button.text(originalText);
                    $button.removeClass('button-secondary').addClass('button');
                }, 2000);
            });
        },

        /**
         * Handle export output
         */
        handleExportOutput: function () {
            const $button = $('#nova_x_export_output');
            const $output = $('#nova_x_customize_output');

            if (!$button.length) {
                return;
            }

            $button.on('click', function () {
                if (!$output.val()) {
                    alert('No content to export. Please generate a theme first.');
                    return;
                }

                // Create a blob and download
                const content = $output.val();
                const blob = new Blob([content], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'nova-x-theme-' + Date.now() + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            });
        },

        /**
         * Handle reset tracker
         */
        handleResetTracker: function () {
            const $button = $('#nova_x_reset_tracker, #nova-x-reset-tracker');
            const $status = $('#nova_x_reset_status, #nova-x-reset-status');

            if (!$button.length) {
                return;
            }

            $button.on('click', function () {
                if (!confirm('Are you sure you want to reset the usage tracker? This will clear all statistics.')) {
                    return;
                }

                $button.prop('disabled', true);
                NovaXDashboard.showStatus($status, '⏳ Resetting tracker...', 'loading');

                $.ajax({
                    method: 'POST',
                    url: novaXDashboard.restUrl + 'reset-usage-tracker',
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                    },
                    data: JSON.stringify({
                        nonce: novaXDashboard.generateNonce || novaXDashboard.nonce,
                    }),
                    timeout: 30000,
                })
                    .done(function (res) {
                        if (res.success) {
                            NovaXDashboard.showStatus($status, '✅ Tracker reset successfully!', 'success');
                            // Reload usage stats after reset
                            setTimeout(function () {
                                NovaXDashboard.loadUsageStats();
                            }, 500);
                        } else {
                            NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Reset failed'), 'error');
                        }
                    })
                    .fail(function (xhr) {
                        NovaXDashboard.showStatus($status, '❌ Request failed. Please try again.', 'error');
                    })
                    .always(function () {
                        $button.prop('disabled', false);
                    });
            });
        },

        /**
         * Handle preview loading
         */
        handlePreviewLoading: function () {
            const $previewFrame = $('#nova-x-preview-frame');
            const $placeholder = $('#nova-x-preview-placeholder');
            const $actions = $('#nova-x-preview-actions');
            const $frameWrapper = $('#nova-x-preview-frame-wrapper');
            const $loadBtn = $('#nova-x-load-preview');
            const $refreshBtn = $('#nova-x-refresh-preview');
            const $status = $('#nova-x-preview-status');
            const $loader = $('#nova-x-preview-loader');
            const $error = $('#nova-x-preview-error');

            if (!$previewFrame.length) {
                return;
            }

            // Function to check and update preview availability
            const checkPreviewAvailability = function() {
                const previewUrl = sessionStorage.getItem('nova_x_preview_url');
                
                if (previewUrl && previewUrl.trim() !== '') {
                    // Show load button, hide placeholder
                    $placeholder.addClass('nova-x-hidden');
                    $actions.removeClass('nova-x-hidden');
                    $frameWrapper.addClass('nova-x-hidden');
                    $error.addClass('nova-x-hidden');
                } else {
                    // Show placeholder, hide everything else
                    $placeholder.removeClass('nova-x-hidden');
                    $actions.addClass('nova-x-hidden');
                    $frameWrapper.addClass('nova-x-hidden');
                    $error.addClass('nova-x-hidden');
                }
            };

            // Check on initial load
            checkPreviewAvailability();

            // Check when switching to preview tab
            $('.nova-x-tab-wrapper .nav-tab[href*="tab=preview"]').on('click', function() {
                setTimeout(checkPreviewAvailability, 100);
            });

            // Handle Load Preview button
            $loadBtn.on('click', function() {
                const previewUrl = sessionStorage.getItem('nova_x_preview_url');
                
                if (!previewUrl || previewUrl.trim() === '') {
                    NovaXDashboard.showStatus($status, '❌ No preview URL available.', 'error');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                $loader.removeClass('nova-x-hidden');
                $error.addClass('nova-x-hidden');
                NovaXDashboard.showStatus($status, '⏳ Loading preview...', 'loading');

                // Hide actions, show frame wrapper
                $actions.addClass('nova-x-hidden');
                $frameWrapper.removeClass('nova-x-hidden');

                // Set iframe source
                $previewFrame.attr('src', previewUrl);

                // Handle iframe load events
                $previewFrame.on('load', function() {
                    $btn.prop('disabled', false);
                    $loader.addClass('nova-x-hidden');
                    NovaXDashboard.showStatus($status, '✅ Preview loaded successfully.', 'success');
                    
                    // Clear status after 3 seconds
                    setTimeout(function() {
                        $status.text('').removeClass('success');
                    }, 3000);
                });

                // Handle iframe load errors (timeout approach)
                const loadTimeout = setTimeout(function() {
                    $previewFrame.off('load');
                    $btn.prop('disabled', false);
                    $loader.addClass('nova-x-hidden');
                    
                    // Check if iframe actually loaded
                    try {
                        const iframeDoc = $previewFrame[0].contentDocument || $previewFrame[0].contentWindow.document;
                        if (!iframeDoc || !iframeDoc.body) {
                            throw new Error('Iframe not accessible');
                        }
                    } catch (e) {
                        // Show error
                        $error.removeClass('nova-x-hidden');
                        NovaXDashboard.showStatus($status, '❌ Preview failed to load. Please try again.', 'error');
                    }
                }, 10000); // 10 second timeout

                $previewFrame.on('load', function() {
                    clearTimeout(loadTimeout);
                });
            });

            // Handle Refresh Preview button
            $refreshBtn.on('click', function() {
                const previewUrl = sessionStorage.getItem('nova_x_preview_url');
                
                if (!previewUrl || previewUrl.trim() === '') {
                    NovaXDashboard.showStatus($status, '❌ No preview URL available.', 'error');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                $error.addClass('nova-x-hidden');
                NovaXDashboard.showStatus($status, '⏳ Refreshing preview...', 'loading');

                // Reload iframe
                $previewFrame.attr('src', 'about:blank');
                
                setTimeout(function() {
                    $previewFrame.attr('src', previewUrl);
                    $btn.prop('disabled', false);
                    
                    setTimeout(function() {
                        NovaXDashboard.showStatus($status, '✅ Preview refreshed.', 'success');
                        setTimeout(function() {
                            $status.text('').removeClass('success');
                        }, 2000);
                    }, 1000);
                }, 300);
            });

            // Store preview URL when it's generated (from other tabs)
            // This can be called from the Generate panel when preview is ready
            $(document).on('nova-x-preview-ready', function(e, previewUrl) {
                if (previewUrl) {
                    sessionStorage.setItem('nova_x_preview_url', previewUrl);
                    checkPreviewAvailability();
                }
            });
        },

        /**
         * Handle customize output panel
         */
        handleCustomizeOutput: function () {
            const $fileTabs = $('.nova-x-file-tab');
            const $status = $('#nova-x-customize-status');
            const $loader = $('#nova-x-customize-loader');
            const $saveBtn = $('#nova-x-save-changes');
            const $resetBtn = $('#nova-x-reset-original');
            const $originalCode = $('#nova-x-original-code');

            if (!$fileTabs.length) {
                return;
            }

            // Handle file tab switching
            $fileTabs.on('click', function () {
                const $tab = $(this);
                const fileType = $tab.data('file');

                // Update active tab
                $fileTabs.removeClass('active');
                $tab.addClass('active');

                // Show corresponding content
                $('.nova-x-file-content').removeClass('active');
                $('#nova-x-file-' + fileType).addClass('active');
                
                // Check empty state for the active tab
                const $editor = $('#nova-x-' + fileType + (fileType === 'style' ? '-css' : '-php'));
                const content = $editor.val() || '';
                checkEmptyState(fileType, content);
            });
            
            // Initialize tooltips
            $('.nova-x-file-tab-info').each(function() {
                const $info = $(this);
                const tooltip = $info.attr('data-tooltip');
                if (tooltip) {
                    $info.on('mouseenter', function() {
                        // Simple tooltip implementation
                        const $tooltip = $('<div class="nova-x-tooltip">' + tooltip + '</div>');
                        $('body').append($tooltip);
                        const offset = $info.offset();
                        $tooltip.css({
                            top: offset.top - $tooltip.outerHeight() - 8,
                            left: offset.left + ($info.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                        });
                    }).on('mouseleave', function() {
                        $('.nova-x-tooltip').remove();
                    });
                }
            });

            // Function to check and display empty state
            const checkEmptyState = function(fileType, content) {
                const isEmpty = !content || content.trim() === '';
                const $editor = $('#nova-x-' + fileType + (fileType === 'style' ? '-css' : '-php'));
                const $emptyState = $('#nova-x-empty-' + fileType);
                
                if (isEmpty) {
                    $editor.hide();
                    $emptyState.show();
                } else {
                    $editor.show();
                    $emptyState.hide();
                }
            };

            // Function to load code from sessionStorage
            const loadCustomizeCode = function() {
                const generatedCode = sessionStorage.getItem('nova_x_generated_code');
                if (generatedCode) {
                    try {
                        const codeData = JSON.parse(generatedCode);
                        
                        // Load style.css
                        if (codeData.style) {
                            $('#nova-x-style-css').val(codeData.style);
                            checkEmptyState('style', codeData.style);
                            // Store original if not already stored
                            if (!$originalCode.attr('data-style')) {
                                $originalCode.attr('data-style', codeData.style);
                            }
                        } else {
                            checkEmptyState('style', '');
                            console.warn('Nova-X: style.css content is empty or not loaded. Please regenerate the theme.');
                        }
                        
                        // Load functions.php
                        if (codeData.functions) {
                            $('#nova-x-functions-php').val(codeData.functions);
                            checkEmptyState('functions', codeData.functions);
                            if (!$originalCode.attr('data-functions')) {
                                $originalCode.attr('data-functions', codeData.functions);
                            }
                        } else {
                            checkEmptyState('functions', '');
                            console.warn('Nova-X: functions.php content is empty or not loaded. Please regenerate the theme.');
                        }
                        
                        // Load index.php
                        if (codeData.index) {
                            $('#nova-x-index-php').val(codeData.index);
                            checkEmptyState('index', codeData.index);
                            if (!$originalCode.attr('data-index')) {
                                $originalCode.attr('data-index', codeData.index);
                            }
                        } else {
                            checkEmptyState('index', '');
                            console.warn('Nova-X: index.php content is empty or not loaded. Please regenerate the theme.');
                        }
                    } catch (e) {
                        console.error('Nova-X: Error parsing generated code:', e);
                        // Show empty states for all files on parse error
                        checkEmptyState('style', '');
                        checkEmptyState('functions', '');
                        checkEmptyState('index', '');
                    }
                } else {
                    // No generated code found - show empty states
                    checkEmptyState('style', '');
                    checkEmptyState('functions', '');
                    checkEmptyState('index', '');
                    console.warn('Nova-X: No generated theme code found. Please generate a theme first.');
                }
            };

            // Load code on initial page load if on customize tab
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'customize') {
                loadCustomizeCode();
            }

            // Also load when clicking customize tab link
            $('.nova-x-tab-wrapper .nav-tab[href*="tab=customize"]').on('click', function() {
                setTimeout(loadCustomizeCode, 100);
            });
            
            // Listen for code updates from other tabs
            $(document).on('nova-x-code-updated', function() {
                setTimeout(loadCustomizeCode, 100);
            });

            // Handle save changes
            $saveBtn.on('click', function () {
                const $btn = $(this);
                
                $btn.prop('disabled', true);
                $loader.removeClass('nova-x-hidden');
                NovaXDashboard.showStatus($status, '⏳ Saving changes...', 'loading');

                // Get current values
                const styleCode = $('#nova-x-style-css').val();
                const functionsCode = $('#nova-x-functions-php').val();
                const indexCode = $('#nova-x-index-php').val();

                // Store updated code in sessionStorage
                const updatedCode = {
                    style: styleCode,
                    functions: functionsCode,
                    index: indexCode,
                    updated: true
                };
                sessionStorage.setItem('nova_x_generated_code', JSON.stringify(updatedCode));

                // Simulate save (in future, this could call REST API)
                setTimeout(function () {
                    $btn.prop('disabled', false);
                    $loader.addClass('nova-x-hidden');
                    NovaXDashboard.showStatus($status, '✅ Changes saved successfully.', 'success');
                    
                    // Clear status after 3 seconds
                    setTimeout(function () {
                        $status.text('').removeClass('success');
                    }, 3000);
                }, 500);
            });

            // Handle reset to original
            $resetBtn.on('click', function () {
                if (!confirm('Are you sure you want to reset all changes? This will restore the original generated code.')) {
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                $loader.removeClass('nova-x-hidden');
                NovaXDashboard.showStatus($status, '⏳ Resetting to original...', 'loading');

                // Restore original values
                const originalStyle = $originalCode.attr('data-style') || '';
                const originalFunctions = $originalCode.attr('data-functions') || '';
                const originalIndex = $originalCode.attr('data-index') || '';

                $('#nova-x-style-css').val(originalStyle);
                $('#nova-x-functions-php').val(originalFunctions);
                $('#nova-x-index-php').val(originalIndex);

                // Restore in sessionStorage
                const originalCode = {
                    style: originalStyle,
                    functions: originalFunctions,
                    index: originalIndex,
                    updated: false
                };
                sessionStorage.setItem('nova_x_generated_code', JSON.stringify(originalCode));

                setTimeout(function () {
                    $btn.prop('disabled', false);
                    $loader.addClass('nova-x-hidden');
                    NovaXDashboard.showStatus($status, '✅ Code reset to original.', 'success');
                    
                    // Clear status after 3 seconds
                    setTimeout(function () {
                        $status.text('').removeClass('success');
                    }, 3000);
                }, 500);
            });
        },

        /**
         * Handle usage stats tab
         */
        handleUsageStats: function () {
            // Load stats when Usage Stats tab is clicked
            $('.nova-x-tab-wrapper .nav-tab[href*="tab=usage"]').on('click', function() {
                setTimeout(function() {
                    NovaXDashboard.loadUsageStats();
                }, 100);
            });

            // Also load on initial page load if already on usage tab
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'usage') {
                setTimeout(function() {
                    NovaXDashboard.loadUsageStats();
                }, 300);
            }
        },

        /**
         * Load usage statistics from REST API
         */
        loadUsageStats: function () {
            const $loader = $('#nova-x-usage-loader');
            const $tbody = $('#nova-x-provider-tbody');
            const $totalTokens = $('#nova-x-total-tokens');
            const $totalCost = $('#nova-x-total-cost');

            if (!$tbody.length) {
                return; // Usage stats panel not present
            }

            // Show loader
            $loader.removeClass('nova-x-hidden');
            $tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px;" class="nova-x-loading-text"><span class="nova-x-loading-text">Loading usage statistics...</span></td></tr>');

            // Fetch stats
            $.ajax({
                method: 'GET',
                url: novaXDashboard.restUrl + 'get-usage-stats',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                },
                timeout: 30000,
            })
                .done(function (res) {
                    if (res.success) {
                        // Update stat cards
                        const formattedTokens = res.total_tokens.toLocaleString();
                        const formattedCost = '$' + res.total_cost.toFixed(4) + ' USD';
                        
                        $totalTokens.text(formattedTokens);
                        $totalCost.text(formattedCost);

                        // Update provider table
                        if (res.providers && res.providers.length > 0) {
                            let tableHTML = '';
                            let hasData = false;

                            res.providers.forEach(function(provider) {
                                if (provider.tokens > 0) {
                                    hasData = true;
                                    const formattedTokens = provider.tokens.toLocaleString();
                                    const formattedCost = '$' + provider.cost.toFixed(4);
                                    
                                    tableHTML += '<tr>';
                                    tableHTML += '<td><strong>' + NovaXDashboard.escapeHtml(provider.provider) + '</strong></td>';
                                    tableHTML += '<td style="text-align: right;">' + formattedTokens + '</td>';
                                    tableHTML += '<td style="text-align: right;">' + formattedCost + '</td>';
                                    tableHTML += '<td style="text-align: right;">' + provider.percentage + '%</td>';
                                    tableHTML += '</tr>';
                                }
                            });

                            if (!hasData) {
                                tableHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;" class="nova-x-loading-text">No usage data yet. Generate a theme to see statistics.</td></tr>';
                            }

                            $tbody.html(tableHTML);
                        } else {
                            $tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px;" class="nova-x-loading-text">No usage data yet. Generate a theme to see statistics.</td></tr>');
                        }
                    } else {
                        $tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px;" class="nova-x-icon-error">Failed to load usage statistics.</td></tr>');
                    }
                })
                .fail(function (xhr) {
                    let errorMessage = 'Failed to load usage statistics.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    $tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px;" class="nova-x-icon-error">' + errorMessage + '</td></tr>');
                })
                .always(function () {
                    $loader.addClass('nova-x-hidden');
                });
        },

        /**
         * Handle exported themes tab
         */
        handleExportedThemes: function () {
            // Load themes when Exported Themes tab is clicked
            $('.nova-x-tab-wrapper .nav-tab[href*="tab=exported"]').on('click', function() {
                setTimeout(function() {
                    NovaXDashboard.loadExportedThemes();
                }, 100);
            });

            // Also load on initial page load if already on exported tab
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'exported') {
                setTimeout(function() {
                    NovaXDashboard.loadExportedThemes();
                }, 300);
            }

            // Handle action buttons (delegated events for dynamically added buttons)
            $(document).on('click', '.nova-x-theme-preview-btn', function() {
                const slug = $(this).data('slug');
                NovaXDashboard.previewExportedTheme(slug);
            });

            $(document).on('click', '.nova-x-theme-install-btn', function() {
                const slug = $(this).data('slug');
                const url = $(this).data('url');
                NovaXDashboard.installExportedTheme(slug, url);
            });

            $(document).on('click', '.nova-x-theme-delete-btn', function() {
                const slug = $(this).data('slug');
                const name = $(this).data('name');
                NovaXDashboard.deleteExportedTheme(slug, name);
            });

            $(document).on('click', '.nova-x-theme-reexport-btn', function() {
                const slug = $(this).data('slug');
                NovaXDashboard.reexportTheme(slug);
            });
        },

        /**
         * Load exported themes from REST API
         */
        loadExportedThemes: function () {
            const $loader = $('#nova-x-exported-themes-loader');
            const $tbody = $('#nova-x-exported-themes-tbody');
            const $empty = $('#nova-x-exported-themes-empty');
            const $tableWrapper = $('#nova-x-exported-themes-table-wrapper');
            const $status = $('#nova-x-exported-themes-status');

            if (!$tbody.length) {
                return; // Exported themes panel not present
            }

            // Show loader
            $loader.removeClass('nova-x-hidden');
            $empty.addClass('nova-x-hidden');
            $tableWrapper.removeClass('nova-x-hidden');
            $tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px;" class="nova-x-loading-text"><span class="nova-x-loading-text">Loading exported themes...</span></td></tr>');

            // Fetch themes
            $.ajax({
                method: 'GET',
                url: novaXDashboard.restUrl + 'list-exported-themes',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                },
                timeout: 30000,
            })
                .done(function (res) {
                    if (res.success && res.themes && res.themes.length > 0) {
                        // Render themes table
                        let tableHTML = '';
                        res.themes.forEach(function(theme) {
                            tableHTML += '<tr data-slug="' + NovaXDashboard.escapeHtml(theme.slug) + '">';
                            tableHTML += '<td><strong>' + NovaXDashboard.escapeHtml(theme.name) + '</strong></td>';
                            tableHTML += '<td>' + NovaXDashboard.escapeHtml(theme.date_formatted) + '</td>';
                            tableHTML += '<td>' + NovaXDashboard.escapeHtml(theme.size_formatted) + '</td>';
                            tableHTML += '<td style="text-align: center;">';
                            tableHTML += '<div class="nova-x-theme-actions">';
                            tableHTML += '<button type="button" class="button button-small nova-x-theme-preview-btn" data-slug="' + NovaXDashboard.escapeHtml(theme.slug) + '" title="Preview">';
                            tableHTML += '<span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span> Preview';
                            tableHTML += '</button> ';
                            tableHTML += '<button type="button" class="button button-small button-primary nova-x-theme-install-btn" data-slug="' + NovaXDashboard.escapeHtml(theme.slug) + '" data-url="' + NovaXDashboard.escapeHtml(theme.url) + '" title="Install">';
                            tableHTML += '<span class="dashicons dashicons-download" style="vertical-align: middle;"></span> Install';
                            tableHTML += '</button> ';
                            tableHTML += '<button type="button" class="button button-small nova-x-theme-reexport-btn" data-slug="' + NovaXDashboard.escapeHtml(theme.slug) + '" title="Re-Export">';
                            tableHTML += '<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Re-Export';
                            tableHTML += '</button> ';
                            tableHTML += '<button type="button" class="button button-small nova-x-theme-delete-btn" data-slug="' + NovaXDashboard.escapeHtml(theme.slug) + '" data-name="' + NovaXDashboard.escapeHtml(theme.name) + '" title="Delete">';
                            tableHTML += '<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Delete';
                            tableHTML += '</button>';
                            tableHTML += '</div>';
                            tableHTML += '</td>';
                            tableHTML += '</tr>';
                        });
                        $tbody.html(tableHTML);
                        $empty.addClass('nova-x-hidden');
                    } else {
                        // Show empty state
                        $tbody.html('');
                        $tableWrapper.addClass('nova-x-hidden');
                        $empty.removeClass('nova-x-hidden');
                    }
                })
                .fail(function (xhr) {
                    let errorMessage = 'Failed to load exported themes.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    $tbody.html('<tr><td colspan="4" style="text-align: center; padding: 20px;" class="nova-x-icon-error">' + errorMessage + '</td></tr>');
                })
                .always(function () {
                    $loader.addClass('nova-x-hidden');
                });
        },

        /**
         * Preview exported theme
         */
        previewExportedTheme: function (slug) {
            // For preview, we need to install the theme first, then open customize
            // This is a simplified version - in production, you'd want to use the preview_theme method
            const customizeUrl = admin_url('customize.php');
            window.open(customizeUrl, '_blank');
            NovaXDashboard.showStatus($('#nova-x-exported-themes-status'), '⏳ Opening theme customizer...', 'loading');
        },

        /**
         * Install exported theme
         */
        installExportedTheme: function (slug, zipUrl) {
            if (!confirm('Are you sure you want to install this theme? This will activate it immediately.')) {
                return;
            }

            const $status = $('#nova-x-exported-themes-status');
            NovaXDashboard.showStatus($status, '⏳ Installing theme...', 'loading');

            $.ajax({
                method: 'POST',
                url: novaXDashboard.restUrl + 'install-theme',
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                },
                data: JSON.stringify({
                    zip_url: zipUrl,
                    nonce: novaXDashboard.generateNonce || novaXDashboard.nonce,
                }),
                timeout: 60000,
            })
                .done(function (res) {
                    if (res.success) {
                        NovaXDashboard.showStatus($status, '✅ Theme installed successfully!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Installation failed'), 'error');
                    }
                })
                .fail(function (xhr) {
                    NovaXDashboard.showStatus($status, '❌ Installation request failed. Please try again.', 'error');
                });
        },

        /**
         * Delete exported theme
         */
        deleteExportedTheme: function (slug, name) {
            if (!confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
                return;
            }

            const $status = $('#nova-x-exported-themes-status');
            const $row = $('tr[data-slug="' + NovaXDashboard.escapeHtml(slug) + '"]');
            
            NovaXDashboard.showStatus($status, '⏳ Deleting theme...', 'loading');
            $row.addClass('nova-x-deleting');

            $.ajax({
                method: 'POST',
                url: novaXDashboard.restUrl + 'delete-exported-theme',
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                },
                data: JSON.stringify({
                    slug: slug,
                    nonce: novaXDashboard.generateNonce || novaXDashboard.nonce,
                }),
                timeout: 30000,
            })
                .done(function (res) {
                    if (res.success) {
                        NovaXDashboard.showStatus($status, '✅ Theme deleted successfully!', 'success');
                        // Remove row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            // Reload if no themes left
                            const remainingRows = $('#nova-x-exported-themes-tbody tr').length;
                            if (remainingRows === 0) {
                                setTimeout(function() {
                                    NovaXDashboard.loadExportedThemes();
                                }, 500);
                            }
                        });
                    } else {
                        NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Delete failed'), 'error');
                        $row.removeClass('nova-x-deleting');
                    }
                })
                .fail(function (xhr) {
                    NovaXDashboard.showStatus($status, '❌ Delete request failed. Please try again.', 'error');
                    $row.removeClass('nova-x-deleting');
                });
        },

        /**
         * Re-export theme
         */
        reexportTheme: function (slug) {
            const $status = $('#nova-x-exported-themes-status');
            const $btn = $('.nova-x-theme-reexport-btn[data-slug="' + NovaXDashboard.escapeHtml(slug) + '"]');
            
            $btn.prop('disabled', true);
            NovaXDashboard.showStatus($status, '⏳ Re-exporting theme...', 'loading');

            $.ajax({
                method: 'POST',
                url: novaXDashboard.restUrl + 'reexport-theme',
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                },
                data: JSON.stringify({
                    slug: slug,
                    nonce: novaXDashboard.generateNonce || novaXDashboard.nonce,
                }),
                timeout: 30000,
            })
                .done(function (res) {
                    if (res.success) {
                        NovaXDashboard.showStatus($status, '✅ Theme re-exported successfully!', 'success');
                        // Reload themes list
                        setTimeout(function() {
                            NovaXDashboard.loadExportedThemes();
                        }, 1000);
                    } else {
                        NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Re-export failed'), 'error');
                        $btn.prop('disabled', false);
                    }
                })
                .fail(function (xhr) {
                    NovaXDashboard.showStatus($status, '❌ Re-export request failed. Please try again.', 'error');
                    $btn.prop('disabled', false);
                });
        },

        /**
         * Escape HTML to prevent XSS
         * 
         * @param {string} text Text to escape
         * @return {string} Escaped text
         */
        escapeHtml: function (text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Show status message
         * 
         * @param {jQuery} $element Status element
         * @param {string} message Message to show
         * @param {string} type Status type (loading, success, error)
         */
        showStatus: function ($element, message, type) {
            $element.removeClass('loading success error')
                .addClass(type)
                .html(message);
        },

        /**
         * Initialize header controls (account, notifications dropdowns)
         */
        initHeaderControls: function () {
            const $accountBtn = $('#nova-x-account-btn');
            const $accountMenu = $('#nova-x-account-menu');
            const $notificationsBtn = $('#nova-x-notifications-btn');
            const $notificationsMenu = $('#nova-x-notifications-menu');
            const $upgradeLink = $('#nova-x-upgrade-link');
            const $headerControls = $('.header-controls');

            // Account dropdown
            if ($accountBtn.length && $accountMenu.length) {
                $accountBtn.on('click', function (e) {
                    e.stopPropagation();
                    const isActive = $accountMenu.hasClass('active');
                    
                    // Close all other dropdowns
                    $notificationsMenu.removeClass('active');
                    $notificationsBtn.attr('aria-expanded', 'false');
                    
                    // Toggle account menu
                    if (isActive) {
                        $accountMenu.removeClass('active');
                        $accountBtn.attr('aria-expanded', 'false');
                    } else {
                        $accountMenu.addClass('active');
                        $accountBtn.attr('aria-expanded', 'true');
                    }
                });
            }

            // Notifications dropdown
            if ($notificationsBtn.length && $notificationsMenu.length) {
                $notificationsBtn.on('click', function (e) {
                    e.stopPropagation();
                    const isActive = $notificationsMenu.hasClass('active');
                    
                    // Close all other dropdowns
                    $accountMenu.removeClass('active');
                    $accountBtn.attr('aria-expanded', 'false');
                    
                    // Toggle notifications menu
                    if (isActive) {
                        $notificationsMenu.removeClass('active');
                        $notificationsBtn.attr('aria-expanded', 'false');
                    } else {
                        $notificationsMenu.addClass('active');
                        $notificationsBtn.attr('aria-expanded', 'true');
                    }
                });
            }

            // Close dropdowns on outside click
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.header-controls').length) {
                    $accountMenu.removeClass('active');
                    $accountBtn.attr('aria-expanded', 'false');
                    $notificationsMenu.removeClass('active');
                    $notificationsBtn.attr('aria-expanded', 'false');
                }
            });

            // Close dropdowns on Escape key
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    $accountMenu.removeClass('active');
                    $accountBtn.attr('aria-expanded', 'false');
                    $notificationsMenu.removeClass('active');
                    $notificationsBtn.attr('aria-expanded', 'false');
                }
            });

            // Upgrade button (placeholder - coming soon)
            if ($upgradeLink.length) {
                $upgradeLink.on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Show "Coming Soon" message
                    const message = 'Upgrade features are coming soon! Stay tuned for premium features and enhanced capabilities.';
                    
                    // Use WordPress admin notice style if available, otherwise simple alert
                    if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/notices')) {
                        // Could use WordPress notices API if needed
                        alert(message);
                    } else {
                        alert(message);
                    }
                    
                    return false;
                });
            }
        },

        /**
         * Initialize theme toggle
         */
        initThemeToggle: function () {
            const $themeToggle = $('#nova-x-theme-toggle');
            const $dashboardWrap = $('.nova-x-dashboard-wrap');
            const $headerBar = $('.nova-x-header-bar');
            const $themeIconLight = $('#nova-x-theme-icon');
            const $themeIconDark = $('#nova-x-theme-icon-dark');

            if (!$themeToggle.length || !$dashboardWrap.length) {
                return;
            }

            // Get saved theme preference or use system preference
            const getSystemTheme = function () {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                    return 'light';
                }
                return 'dark';
            };

            const savedTheme = localStorage.getItem('nova_x_theme_preference');
            const initialTheme = savedTheme || getSystemTheme();
            
            // Apply initial theme
            $dashboardWrap.attr('data-theme', initialTheme);
            if ($headerBar.length) {
                $headerBar.attr('data-theme', initialTheme);
            }
            document.documentElement.setAttribute('data-theme', initialTheme);
            
            // Update icon visibility
            this.updateThemeIcon($themeIconLight, $themeIconDark, initialTheme);

            // Handle theme toggle click
            $themeToggle.on('click', function () {
                const currentTheme = $dashboardWrap.attr('data-theme') || 'dark';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                
                // Apply new theme instantly
                $dashboardWrap.attr('data-theme', newTheme);
                if ($headerBar.length) {
                    $headerBar.attr('data-theme', newTheme);
                }
                document.documentElement.setAttribute('data-theme', newTheme);
                
                // Save preference
                localStorage.setItem('nova_x_theme_preference', newTheme);
                
                // Update icon visibility
                NovaXDashboard.updateThemeIcon($themeIconLight, $themeIconDark, newTheme);
                
                // Save to user meta via AJAX (optional, for persistence across devices)
                if (typeof novaXDashboard !== 'undefined' && novaXDashboard.restUrl) {
                    $.ajax({
                        method: 'POST',
                        url: novaXDashboard.restUrl + 'update-theme-preference',
                        contentType: 'application/json',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                        },
                        data: JSON.stringify({
                            theme: newTheme,
                            nonce: novaXDashboard.generateNonce || novaXDashboard.nonce,
                        }),
                        timeout: 5000,
                    }).fail(function() {
                        // Silently fail - localStorage is sufficient
                    });
                }
            });

            // Listen for system theme changes (optional)
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: light)');
                mediaQuery.addEventListener('change', function (e) {
                    // Only apply if user hasn't set a preference
                    if (!localStorage.getItem('nova_x_theme_preference')) {
                        const systemTheme = e.matches ? 'light' : 'dark';
                        $dashboardWrap.attr('data-theme', systemTheme);
                        if ($headerBar.length) {
                            $headerBar.attr('data-theme', systemTheme);
                        }
                        document.documentElement.setAttribute('data-theme', systemTheme);
                        NovaXDashboard.updateThemeIcon($themeIconLight, $themeIconDark, systemTheme);
                    }
                });
            }
        },

        /**
         * Update theme icon visibility based on current theme
         * 
         * @param {jQuery} $lightIcon Light theme icon element
         * @param {jQuery} $darkIcon Dark theme icon element
         * @param {string} theme Current theme (light/dark)
         */
        updateThemeIcon: function ($lightIcon, $darkIcon, theme) {
            if (theme === 'light') {
                if ($lightIcon.length) $lightIcon.show();
                if ($darkIcon.length) $darkIcon.hide();
            } else {
                if ($lightIcon.length) $lightIcon.hide();
                if ($darkIcon.length) $darkIcon.show();
            }
        },

        /**
         * Initialize notice dismiss functionality
         */
        initNotices: function () {
            // Handle dismissible notices
            $(document).on('click', '.nova-x-notice-dismiss', function() {
                const $notice = $(this).closest('.nova-x-notice');
                $notice.addClass('nova-x-notice-dismissed');
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            });
        },

        /**
         * Initialize header overlay positioning
         */
        initHeaderOverlay: function () {
            const $headerOverlay = $('.nova-x-header-overlay');
            if (!$headerOverlay.length) {
                return;
            }

            // Function to update header position based on sidebar state
            const updateHeaderPosition = function() {
                const $body = $('body');
                const isFolded = $body.hasClass('folded');
                
                if (isFolded) {
                    $headerOverlay.css('left', '36px');
                } else {
                    $headerOverlay.css('left', '160px');
                }
            };

            // Initial position
            updateHeaderPosition();

            // Watch for sidebar toggle
            $(document).on('wp-collapse-menu', function() {
                setTimeout(updateHeaderPosition, 300); // Wait for animation
            });

            // Also watch for window resize (responsive)
            $(window).on('resize', function() {
                updateHeaderPosition();
            });
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function () {
        NovaXDashboard.init();
    });

})(jQuery);

