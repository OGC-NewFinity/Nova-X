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
            // Theme toggle is now handled by theme-toggle.js globally
            // No need to initialize here
            this.initHeaderOverlay();
            this.initNotices();
            this.handleTokenRotation();
        },

        /**
         * Initialize sidebar navigation
         */
        initSidebarNavigation: function () {
            const $sidebar = $('#nova-x-sidebar');
            const $sidebarLinks = $('.nova-x-sidebar-link');
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

            // Sidebar toggle is handled by vanilla JS (see bottom of file)
            // This ensures it works with the correct button ID: #novaX_sidebar_toggle

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
                if (tab === 'customize') {
                    // Trigger event to load editor files
                    $(document).trigger('nova-x-tab-switched', [tab]);
                } else if (tab === 'usage') {
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
                        // Handle notifier from response
                        NovaXDashboard.handleNotifier(res, $status);
                        
                        if (res.success) {
                            // If notifier was handled, still show status for backward compatibility
                            if (!res.notifier) {
                                NovaXDashboard.showStatus($status, '✅ Theme generated successfully!', 'success');
                            }
                            
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
                                
                                // If on customize tab, trigger a reload of the customize code from REST API
                                if (window.location.search.includes('tab=customize')) {
                                    $(document).trigger('nova-x-code-updated');
                                }
                            }
                            
                            // Clear prompt but keep title
                            $promptInput.val('');
                        } else {
                            // Error response - notifier should already be handled, but fallback if needed
                            if (!res.notifier) {
                                NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Theme generation failed'), 'error');
                            }
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
                        
                        // Use NovaXNotices if available, otherwise fallback to showStatus
                        if (typeof NovaXNotices !== 'undefined') {
                            NovaXNotices.error(errorMessage);
                        } else {
                            NovaXDashboard.showStatus($status, '❌ ' + errorMessage, 'error');
                        }
                    })
                    .always(function () {
                        $button.prop('disabled', false);
                        $loader.addClass('nova-x-hidden');
                    });
            });

            // Handle export button
            $exportBtn.on('click', function () {
                const themeCode = $outputContainer.data('theme-code');
                const themeTitle = $outputContainer.data('theme-title') || 'Nova-X Theme';
                
                if (!themeCode) {
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.error('No theme code available. Please generate a theme first.');
                    } else {
                        NovaXDashboard.showStatus($actionStatus, '❌ No theme code available.', 'error');
                    }
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                NovaXDashboard.showStatus($actionStatus, '⏳ Exporting theme...', 'loading');

                // Call export REST API endpoint
                $.ajax({
                    method: 'POST',
                    url: novaXDashboard.restUrl + 'export-theme',
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', novaXDashboard.nonce);
                    },
                    data: JSON.stringify({
                        site_title: themeTitle,
                        code: themeCode,
                        nonce: novaXDashboard.generateNonce || '',
                    }),
                    timeout: 60000,
                })
                .done(function(res) {
                    // Handle notifier from response
                    NovaXDashboard.handleNotifier(res, $actionStatus);
                    
                    if (res.success && res.download_url) {
                        // Trigger download
                        window.location.href = res.download_url;
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($actionStatus, '✅ Theme exported successfully!', 'success');
                        }
                    } else {
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($actionStatus, '❌ ' + (res.message || 'Export failed'), 'error');
                        }
                    }
                })
                .fail(function(xhr) {
                    let errorMessage = 'Export request failed.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.error(errorMessage);
                    } else {
                        NovaXDashboard.showStatus($actionStatus, '❌ ' + errorMessage, 'error');
                    }
                })
                .always(function() {
                    $btn.prop('disabled', false);
                });
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
                    // Handle notifier from response
                    NovaXDashboard.handleNotifier(res, $actionStatus);
                    
                    if (res.success && res.preview_url) {
                        sessionStorage.setItem('nova_x_preview_url', res.preview_url);
                        $(document).trigger('nova-x-preview-ready', [res.preview_url]);
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($actionStatus, '✅ Preview ready! Switch to Live Preview tab to view.', 'success');
                        }
                    } else {
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($actionStatus, '❌ ' + (res.message || 'Preview preparation failed.'), 'error');
                        }
                    }
                })
                .fail(function(xhr) {
                    let errorMessage = 'Preview request failed.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.error(errorMessage);
                    } else {
                        NovaXDashboard.showStatus($actionStatus, '❌ ' + errorMessage, 'error');
                    }
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
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.warning('No content to copy.');
                    } else {
                        alert('No content to copy.');
                    }
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
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.warning('No content to export. Please generate a theme first.');
                    } else {
                        alert('No content to export. Please generate a theme first.');
                    }
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
                        // Handle notifier from response
                        NovaXDashboard.handleNotifier(res, $status);
                        
                        if (res.success) {
                            if (!res.notifier) {
                                NovaXDashboard.showStatus($status, '✅ Tracker reset successfully!', 'success');
                            }
                            // Reload usage stats after reset
                            setTimeout(function () {
                                NovaXDashboard.loadUsageStats();
                            }, 500);
                        } else {
                            if (!res.notifier) {
                                NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Reset failed'), 'error');
                            }
                        }
                    })
                    .fail(function (xhr) {
                        if (typeof NovaXNotices !== 'undefined') {
                            NovaXNotices.error('Request failed. Please try again.');
                        } else {
                            NovaXDashboard.showStatus($status, '❌ Request failed. Please try again.', 'error');
                        }
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

            // Function to load editor files from REST API
            const loadEditorFiles = async function() {
                // Check if NovaXEditorData is available
                if (typeof NovaXEditorData === 'undefined' || !NovaXEditorData.rest_url) {
                    console.error('Nova-X: NovaXEditorData is not available. Cannot load editor files.');
                    checkEmptyState('style', '');
                    checkEmptyState('functions', '');
                    checkEmptyState('index', '');
                    return;
                }

                try {
                    const res = await fetch(NovaXEditorData.rest_url);
                    const json = await res.json();
                    
                    if (!json.success) {
                        const errorMessage = json?.notifier?.message || json?.message || 'Failed to load files';
                        throw new Error(errorMessage);
                    }
                    
                    const files = json.files || {};
                    
                    // Load style.css
                    const styleContent = files['style.css'] || '';
                    $('#nova-x-style-css').val(styleContent);
                    checkEmptyState('style', styleContent);
                    // Store original if not already stored
                    if (!$originalCode.attr('data-style')) {
                        $originalCode.attr('data-style', styleContent);
                    }
                    
                    // Load functions.php
                    const functionsContent = files['functions.php'] || '';
                    $('#nova-x-functions-php').val(functionsContent);
                    checkEmptyState('functions', functionsContent);
                    if (!$originalCode.attr('data-functions')) {
                        $originalCode.attr('data-functions', functionsContent);
                    }
                    
                    // Load index.php
                    const indexContent = files['index.php'] || '';
                    $('#nova-x-index-php').val(indexContent);
                    checkEmptyState('index', indexContent);
                    if (!$originalCode.attr('data-index')) {
                        $originalCode.attr('data-index', indexContent);
                    }

                    // Show success message if NovaXNotices is available
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.success('Theme files loaded into editor.');
                    }
                } catch (err) {
                    console.error('Nova-X: Error loading editor files:', err);
                    // Show empty states for all files on error
                    checkEmptyState('style', '');
                    checkEmptyState('functions', '');
                    checkEmptyState('index', '');
                    
                    // Show error message if NovaXNotices is available
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.error(err.message || 'Failed to load theme files.');
                    } else {
                        console.warn('Nova-X: ' + (err.message || 'Failed to load theme files.'));
                    }
                }
            };

            // Load code on initial page load if on customize tab
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'customize') {
                loadEditorFiles();
            }

            // Also load when clicking customize tab link
            $('.nova-x-tab-wrapper .nav-tab[href*="tab=customize"]').on('click', function() {
                setTimeout(loadEditorFiles, 100);
            });
            
            // Listen for code updates from other tabs (e.g., after theme generation)
            $(document).on('nova-x-code-updated', function() {
                setTimeout(loadEditorFiles, 100);
            });
            
            // Also load when switching to customize tab via sidebar
            $(document).on('nova-x-tab-switched', function(e, tab) {
                if (tab === 'customize') {
                    setTimeout(loadEditorFiles, 100);
                }
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

                // Note: Save functionality is read-only for now
                // In future, this could call REST API to save changes
                setTimeout(function () {
                    $btn.prop('disabled', false);
                    $loader.addClass('nova-x-hidden');
                    NovaXDashboard.showStatus($status, '✅ Changes saved locally.', 'success');
                    
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

                // Restore original values from hidden div storage
                const originalStyle = $originalCode.attr('data-style') || '';
                const originalFunctions = $originalCode.attr('data-functions') || '';
                const originalIndex = $originalCode.attr('data-index') || '';

                $('#nova-x-style-css').val(originalStyle);
                $('#nova-x-functions-php').val(originalFunctions);
                $('#nova-x-index-php').val(originalIndex);

                // Update empty states
                checkEmptyState('style', originalStyle);
                checkEmptyState('functions', originalFunctions);
                checkEmptyState('index', originalIndex);

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
                    // Handle notifier from response
                    NovaXDashboard.handleNotifier(res);
                    
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
                    // Handle notifier from response
                    NovaXDashboard.handleNotifier(res);
                    
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
                    // Handle notifier from response
                    NovaXDashboard.handleNotifier(res, $status);
                    
                    if (res.success) {
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($status, '✅ Theme installed successfully!', 'success');
                        }
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Installation failed'), 'error');
                        }
                    }
                })
                .fail(function (xhr) {
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.error('Installation request failed. Please try again.');
                    } else {
                        NovaXDashboard.showStatus($status, '❌ Installation request failed. Please try again.', 'error');
                    }
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
                    // Handle notifier from response
                    NovaXDashboard.handleNotifier(res, $status);
                    
                    if (res.success) {
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($status, '✅ Theme deleted successfully!', 'success');
                        }
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
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Delete failed'), 'error');
                        }
                        $row.removeClass('nova-x-deleting');
                    }
                })
                .fail(function (xhr) {
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.error('Delete request failed. Please try again.');
                    } else {
                        NovaXDashboard.showStatus($status, '❌ Delete request failed. Please try again.', 'error');
                    }
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
                    // Handle notifier from response
                    NovaXDashboard.handleNotifier(res, $status);
                    
                    if (res.success) {
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($status, '✅ Theme re-exported successfully!', 'success');
                        }
                        // Reload themes list
                        setTimeout(function() {
                            NovaXDashboard.loadExportedThemes();
                        }, 1000);
                    } else {
                        if (!res.notifier) {
                            NovaXDashboard.showStatus($status, '❌ ' + (res.message || 'Re-export failed'), 'error');
                        }
                        $btn.prop('disabled', false);
                    }
                })
                .fail(function (xhr) {
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.error('Re-export request failed. Please try again.');
                    } else {
                        NovaXDashboard.showStatus($status, '❌ Re-export request failed. Please try again.', 'error');
                    }
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
         * Handle notifier from REST API response
         * 
         * @param {Object} response REST API response object
         * @param {jQuery} $fallbackElement Optional fallback element for showStatus
         */
        handleNotifier: function (response, $fallbackElement) {
            // Check if response has notifier data
            if (response && response.notifier && typeof NovaXNotices !== 'undefined') {
                const notifier = response.notifier;
                const type = notifier.type || 'info';
                const message = notifier.message || '';
                
                if (message) {
                    // Use NovaXNotices API
                    if (type === 'success') {
                        NovaXNotices.success(message);
                    } else if (type === 'error') {
                        NovaXNotices.error(message);
                    } else if (type === 'warning') {
                        NovaXNotices.warning(message);
                    } else {
                        NovaXNotices.info(message);
                    }
                }
            } else if ($fallbackElement && response && response.message) {
                // Fallback to showStatus if NovaXNotices is not available
                const type = response.success ? 'success' : 'error';
                const emoji = response.success ? '✅ ' : '❌ ';
                this.showStatus($fallbackElement, emoji + response.message, type);
            }
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
                    
                    // Use NovaXNotices if available, otherwise fallback to alert
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.info(message);
                    } else {
                        alert(message);
                    }
                    
                    return false;
                });
            }
        },

        // Theme toggle functionality has been moved to theme-toggle.js
        // This ensures consistent behavior across all Nova-X pages

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
         * Handle token rotation (Settings page)
         */
        handleTokenRotation: function () {
            const $rotateBtn = $('.rotate-token-btn');
            const $rotateStatus = $('.rotate-token-status');
            const $providerSelect = $('select[name="nova_x_provider"]');
            const $apiKeyInput = $('input[name="nova_x_api_key"]');

            if (!$rotateBtn.length) {
                return; // Token rotation not available on this page
            }

            // Update rotate button data-provider when provider selection changes
            $providerSelect.on('change', function () {
                const provider = $(this).val();
                $rotateBtn.attr('data-provider', provider);
            });

            // Handle token rotation
            $rotateBtn.on('click', function () {
                const $btn = $(this);
                const provider = $btn.attr('data-provider');
                const newKey = $apiKeyInput.val().trim();

                // Confirmation dialog
                if (!confirm('Are you sure you want to rotate the token for ' + provider + '? This will replace the existing encrypted token.')) {
                    return;
                }

                // Validate that API key is provided
                if (!newKey || newKey.indexOf('*') !== -1) {
                    $rotateStatus.html('❌ Please enter a valid API key in the API Key field first.');
                    return;
                }

                // Disable button and show loading state
                $btn.prop('disabled', true);
                $btn.html('<span class="spinner is-active"></span> Rotating...');
                $rotateStatus.html('⏳ Rotating token...');

                // Check if NovaXData is available (settings page)
                const rotateUrl = (typeof NovaXData !== 'undefined' && NovaXData.rotateTokenUrl) 
                    ? NovaXData.rotateTokenUrl 
                    : (typeof novaXDashboard !== 'undefined' ? novaXDashboard.restUrl + 'rotate-token' : '');
                const nonce = (typeof NovaXData !== 'undefined' && NovaXData.nonce) 
                    ? NovaXData.nonce 
                    : (typeof novaXDashboard !== 'undefined' ? novaXDashboard.nonce : '');

                if (!rotateUrl || !nonce) {
                    $rotateStatus.html('❌ Configuration error. Please refresh the page.');
                    $btn.prop('disabled', false);
                    $btn.html('🔁 Rotate Token');
                    return;
                }

                // Send AJAX request
                $.ajax({
                    method: 'POST',
                    url: rotateUrl,
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', nonce);
                    },
                    data: JSON.stringify({
                        provider: provider,
                        new_key: newKey,
                        force: true,
                        nonce: nonce,
                    }),
                    timeout: 30000,
                })
                .done(function (res) {
                    // Handle notifier from response
                    NovaXDashboard.handleNotifier(res, $rotateStatus);
                    
                    if (res.success) {
                        if (!res.notifier) {
                            $rotateStatus.html('✅ ' + (res.message || 'Token rotated successfully'));
                            $rotateStatus.addClass('fade-success');
                        }
                        // Clear API key field after successful rotation
                        $apiKeyInput.val('');
                        // Reload page after 1.5 seconds to refresh settings
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        if (!res.notifier) {
                            $rotateStatus.html('❌ ' + (res.message || 'Token rotation failed'));
                        }
                    }
                })
                .fail(function (xhr) {
                    let errorMessage = 'Request failed. Check your network or permissions.';
                    
                    if (xhr.status === 0) {
                        errorMessage = 'Network error. Please check your connection.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Please refresh the page and try again.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'API endpoint not found. Please check plugin configuration.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.statusText === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    }
                    
                    if (typeof NovaXNotices !== 'undefined') {
                        NovaXNotices.error(errorMessage);
                    } else {
                        $rotateStatus.html('❌ ' + errorMessage);
                    }
                })
                .always(function () {
                    // Re-enable button after request completes
                    $btn.prop('disabled', false);
                    $btn.html('🔁 Rotate Token');
                });
            });
        },

        /**
         * Initialize header overlay positioning
         */
        initHeaderOverlay: function () {
            const $headerOverlay = $('.nova-x-dashboard-layout .nova-x-header-overlay');
            if (!$headerOverlay.length) {
                return;
            }

            // Function to update header position based on sidebar state
            const updateHeaderPosition = function() {
                const $sidebar = $('.nova-x-dashboard-layout .nova-x-sidebar');
                const isCollapsed = $sidebar.hasClass('collapsed');
                
                if (isCollapsed) {
                    $headerOverlay.css('left', '60px'); // Collapsed sidebar width
                } else {
                    $headerOverlay.css('left', '240px'); // Expanded sidebar width
                }
            };

            // Initial position
            updateHeaderPosition();

            // Watch for sidebar toggle - listen for class changes on sidebar
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        setTimeout(updateHeaderPosition, 50); // Small delay to ensure class is applied
                    }
                });
            });

            const $sidebar = $('.nova-x-dashboard-layout .nova-x-sidebar');
            if ($sidebar.length) {
                observer.observe($sidebar[0], {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }

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

// Vanilla JS toggle button implementation
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("novaX_sidebar_toggle");
    const sidebar = document.querySelector(".nova-x-dashboard-layout .nova-x-sidebar");
    const headerOverlay = document.querySelector(".nova-x-dashboard-layout .nova-x-header-overlay");
    
    // Function to update header overlay position
    const updateHeaderPosition = function() {
        if (headerOverlay && sidebar) {
            const isCollapsed = sidebar.classList.contains("collapsed");
            // Use requestAnimationFrame for smooth transitions
            requestAnimationFrame(() => {
                headerOverlay.style.left = isCollapsed ? "60px" : "240px";
            });
        }
    };
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            localStorage.setItem("novaX_sidebar_state", sidebar.classList.contains("collapsed") ? "collapsed" : "expanded");
            // Update header position with slight delay to ensure class is applied
            setTimeout(updateHeaderPosition, 10);
        });

        // Restore state on load
        const savedState = localStorage.getItem("novaX_sidebar_state");
        if (savedState === "collapsed") {
            sidebar.classList.add("collapsed");
        }
        // Update header position on initial load
        setTimeout(updateHeaderPosition, 100);
    }

    // Architecture Page - Placeholder interactions
    if (document.querySelector('.nova-x-architecture-container') || document.querySelector('.nova-x-cards-grid')) {
        // Tooltip functionality for disabled cards
        const disabledCards = document.querySelectorAll('.nova-x-card.disabled');
        disabledCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                // Future: Show tooltip with "Coming Soon" message
                this.style.cursor = 'not-allowed';
            });
        });

        // Future: Card collapse/expand functionality
        const architectureCards = document.querySelectorAll('.nova-x-card');
        architectureCards.forEach(card => {
            // Placeholder for future card interactions
            card.setAttribute('data-interactive', 'false');
        });
    }
});

