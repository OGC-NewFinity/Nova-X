jQuery(document).ready(function ($) {
    const $button = $('#nova_x_generate_theme');
    const $status = $('#nova_x_response');
    const $titleInput = $('#nova_x_site_title');
    const $promptInput = $('#nova_x_prompt');
    const $rotateBtn = $('.rotate-token-btn');
    const $rotateStatus = $('.rotate-token-status');
    const $providerSelect = $('select[name="nova_x_provider"]');
    const $apiKeyInput = $('input[name="nova_x_api_key"]');
    const $resetTrackerBtn = $('#nova_x_reset_tracker');
    const $resetTrackerStatus = $('#nova_x_reset_status');
    
    // Store generated code for export
    let generatedCode = '';
    let generatedTitle = '';
    let exportedZipUrl = '';
    
    // Create export button container (initially hidden)
    const $exportContainer = $('<div id="nova_x_export_container" style="margin-top: 15px; display: none;"></div>');
    $status.after($exportContainer);
    
    const $exportBtn = $('<button type="button" class="button button-secondary" id="nova_x_export_theme">📦 Export Theme</button>');
    const $exportStatus = $('<span id="nova_x_export_status" style="margin-left: 10px; font-weight: bold;"></span>');
    const $actionButtons = $('<div id="nova_x_action_buttons" style="margin-top: 10px; display: none;"></div>');
    const $previewBtn = $('<button type="button" class="button button-secondary" id="nova_x_preview_theme" style="margin-right: 10px;">👁️ Preview</button>');
    const $installBtn = $('<button type="button" class="button button-primary" id="nova_x_install_theme" style="margin-right: 10px;">⚡ Install</button>');
    const $actionStatus = $('<span id="nova_x_action_status" style="font-weight: bold;"></span>');
    
    $exportContainer.append($exportBtn).append($exportStatus);
    $actionButtons.append($previewBtn).append($installBtn).append($actionStatus);
    $exportContainer.append($actionButtons);

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

        // Send AJAX request
        $.ajax({
            method: 'POST',
            url: NovaXData.rotateTokenUrl,
            contentType: 'application/json',
            data: JSON.stringify({
                provider: provider,
                new_key: newKey,
                force: true,
                nonce: NovaXData.nonce,
            }),
            timeout: 30000,
        })
        .done(function (res) {
            if (res.success) {
                $rotateStatus.html('✅ ' + res.message);
                $rotateStatus.addClass('fade-success');
                // Clear API key field after successful rotation
                $apiKeyInput.val('');
                // Reload page after 1.5 seconds to refresh settings
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                $rotateStatus.html('❌ ' + (res.message || 'Token rotation failed'));
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
            
            $rotateStatus.html('❌ ' + errorMessage);
        })
        .always(function () {
            // Re-enable button after request completes
            $btn.prop('disabled', false);
            $btn.html('🔁 Rotate Token');
        });
    });

    // Handle theme export
    $exportBtn.on('click', function () {
        const $btn = $(this);
        
        // Validate that we have code to export
        if (!generatedCode || !generatedTitle) {
            $exportStatus.html('❌ No theme code available. Please generate a theme first.');
            return;
        }

        // Disable button and show loading state
        $btn.prop('disabled', true);
        $btn.html('<span class="spinner is-active"></span> Exporting...');
        $exportStatus.html('⏳ Creating ZIP archive...');

        // Send AJAX request
        $.ajax({
            method: 'POST',
            url: NovaXData.exportThemeUrl,
            contentType: 'application/json',
            data: JSON.stringify({
                site_title: generatedTitle,
                code: generatedCode,
                nonce: NovaXData.nonce,
            }),
            timeout: 60000, // 60 second timeout for ZIP creation
        })
        .done(function (res) {
            if (res.success && res.download_url) {
                $exportStatus.html('✅ ' + (res.message || 'Theme exported successfully!'));
                $exportStatus.addClass('fade-success');
                
                // Store ZIP URL for preview/install
                exportedZipUrl = res.download_url;
                
                // Create download link
                const $downloadLink = $('<a href="' + res.download_url + '" class="button" style="margin-left: 10px;" download>⬇️ Download ' + res.filename + '</a>');
                $exportStatus.after($downloadLink);
                
                // Show preview and install buttons
                $actionButtons.show();
                $actionStatus.html('');
                
                // Auto-trigger download
                window.location.href = res.download_url;
            } else {
                $exportStatus.html('❌ ' + (res.message || 'Theme export failed'));
                $actionButtons.hide();
                exportedZipUrl = '';
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
            
            $exportStatus.html('❌ ' + errorMessage);
            $actionButtons.hide();
            exportedZipUrl = '';
        })
        .always(function () {
            // Re-enable button after request completes
            $btn.prop('disabled', false);
            $btn.html('📦 Export Theme');
        });
    });

    // Handle theme preview
    $previewBtn.on('click', function () {
        const $btn = $(this);
        
        if (!exportedZipUrl) {
            $actionStatus.html('❌ No exported theme available. Please export first.');
            return;
        }

        // Disable button and show loading state
        $btn.prop('disabled', true);
        $btn.html('<span class="spinner is-active"></span> Preparing...');
        $actionStatus.html('⏳ Setting up preview...');

        // Send AJAX request
        $.ajax({
            method: 'POST',
            url: NovaXData.previewThemeUrl,
            contentType: 'application/json',
            data: JSON.stringify({
                zip_url: exportedZipUrl,
                nonce: NovaXData.nonce,
            }),
            timeout: 60000,
        })
        .done(function (res) {
            if (res.success && res.preview_url) {
                $actionStatus.html('✅ Preview ready! Opening in new tab...');
                $actionStatus.addClass('fade-success');
                
                // Open preview in new tab
                window.open(res.preview_url, '_blank');
                
                // Reset status after 2 seconds
                setTimeout(function () {
                    $actionStatus.html('');
                }, 2000);
            } else {
                $actionStatus.html('❌ ' + (res.message || 'Preview failed'));
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
            
            $actionStatus.html('❌ ' + errorMessage);
        })
        .always(function () {
            // Re-enable button after request completes
            $btn.prop('disabled', false);
            $btn.html('👁️ Preview');
        });
    });

    // Handle theme installation
    $installBtn.on('click', function () {
        const $btn = $(this);
        
        if (!exportedZipUrl) {
            $actionStatus.html('❌ No exported theme available. Please export first.');
            return;
        }

        // Confirmation dialog
        if (!confirm('Are you sure you want to install and activate this theme? This will replace your current active theme.')) {
            return;
        }

        // Disable button and show loading state
        $btn.prop('disabled', true);
        $btn.html('<span class="spinner is-active"></span> Installing...');
        $actionStatus.html('⏳ Installing and activating theme...');

        // Send AJAX request
        $.ajax({
            method: 'POST',
            url: NovaXData.installThemeUrl,
            contentType: 'application/json',
            data: JSON.stringify({
                zip_url: exportedZipUrl,
                nonce: NovaXData.nonce,
            }),
            timeout: 60000,
        })
        .done(function (res) {
            if (res.success) {
                $actionStatus.html('✅ ' + (res.message || 'Theme installed and activated!'));
                $actionStatus.addClass('fade-success');
                
                // Show success banner
                const $banner = $('<div class="notice notice-success is-dismissible" style="margin: 15px 0; padding: 10px;"><p><strong>Success!</strong> Theme "' + (res.theme_name || res.theme_slug) + '" has been installed and activated.</p></div>');
                $('.wrap h1').after($banner);
                
                // Reload page after 2 seconds to show new theme
                setTimeout(function () {
                    location.reload();
                }, 2000);
            } else {
                $actionStatus.html('❌ ' + (res.message || 'Installation failed'));
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
            
            $actionStatus.html('❌ ' + errorMessage);
        })
        .always(function () {
            // Re-enable button after request completes
            $btn.prop('disabled', false);
            $btn.html('⚡ Install');
        });
    });

    $button.on('click', function () {
        // Get and trim input values
        const title = $titleInput.val().trim();
        const prompt = $promptInput.val().trim();

        // Validate required fields
        if (!title || !prompt) {
            $status.html('⚠️ Site Title and Prompt are required.');
            return;
        }

        // Disable button and show loading state
        $button.prop('disabled', true);
        $status.html('⏳ Generating theme...');

        // Send AJAX request
        $.ajax({
            method: 'POST',
            url: NovaXData.restUrl,
            contentType: 'application/json',
            data: JSON.stringify({
                title,
                prompt,
                nonce: NovaXData.nonce,
            }),
            timeout: 60000, // 60 second timeout for slow networks
        })
        .done(function (res) {
            if (res.success) {
                $status.html('✅ ' + (res.message || 'Theme generated successfully!'));
                // Optional: Add success animation class
                $status.addClass('fade-success');
                
                // Store generated code and title for export
                if (res.output) {
                    generatedCode = res.output;
                    generatedTitle = title;
                    // Show export button
                    $exportContainer.show();
                    $exportStatus.html('');
                }
                
                // Clear form inputs on success (but keep title for export)
                $promptInput.val('');
            } else {
                $status.html('❌ ' + (res.message || 'Theme generation failed'));
                // Hide export button on failure
                $exportContainer.hide();
                generatedCode = '';
                generatedTitle = '';
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
            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            } else if (xhr.statusText === 'timeout') {
                errorMessage = 'Request timed out. Please try again.';
            }
            
            $status.html('❌ ' + errorMessage);
        })
        .always(function () {
            // Re-enable button after request completes
            $button.prop('disabled', false);
        });
    });

    // Handle reset usage tracker
    $('#nova_x_reset_tracker').on('click', function () {
        const $btn = $(this);
        const $status = $('#nova_x_reset_status');
        
        // Confirmation dialog
        if (!confirm('Are you sure you want to reset the usage tracker? This will clear all token and cost statistics.')) {
            return;
        }

        // Disable button and show loading state
        $btn.prop('disabled', true);
        $btn.html('<span class="spinner is-active"></span> Resetting...');
        $status.html('⏳ Resetting tracker...');

        // Send AJAX request
        $.ajax({
            method: 'POST',
            url: NovaXData.resetTrackerUrl,
            contentType: 'application/json',
            data: JSON.stringify({
                nonce: NovaXData.nonce,
            }),
            timeout: 30000,
        })
        .done(function (res) {
            if (res.success) {
                $status.html('✅ ' + (res.message || 'Tracker reset successfully!'));
                $status.addClass('fade-success');
                
                // Reload page after 1.5 seconds to refresh statistics
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                $status.html('❌ ' + (res.message || 'Reset failed'));
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
            
            $status.html('❌ ' + errorMessage);
        })
        .always(function () {
            // Re-enable button after request completes
            $btn.prop('disabled', false);
            $btn.html('🔄 Reset Tracker');
        });
    });
});

