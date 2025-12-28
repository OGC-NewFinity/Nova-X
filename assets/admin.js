jQuery(document).ready(function ($) {
    const $button = $('#nova_x_generate_theme');
    const $status = $('#nova_x_response');
    const $titleInput = $('#nova_x_site_title');
    const $promptInput = $('#nova_x_prompt');
    const $rotateBtn = $('.rotate-token-btn');
    const $rotateStatus = $('.rotate-token-status');
    const $providerSelect = $('select[name="nova_x_provider"]');
    const $apiKeyInput = $('input[name="nova_x_api_key"]');
    
    // Store generated code for export
    let generatedCode = '';
    let generatedTitle = '';
    
    // Create export button container (initially hidden)
    const $exportContainer = $('<div id="nova_x_export_container" style="margin-top: 15px; display: none;"></div>');
    $status.after($exportContainer);
    
    const $exportBtn = $('<button type="button" class="button button-secondary" id="nova_x_export_theme">📦 Export Theme</button>');
    const $exportStatus = $('<span id="nova_x_export_status" style="margin-left: 10px; font-weight: bold;"></span>');
    $exportContainer.append($exportBtn).append($exportStatus);

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
                
                // Create download link
                const $downloadLink = $('<a href="' + res.download_url + '" class="button button-primary" style="margin-left: 10px;" download>⬇️ Download ' + res.filename + '</a>');
                $exportStatus.after($downloadLink);
                
                // Auto-trigger download
                window.location.href = res.download_url;
            } else {
                $exportStatus.html('❌ ' + (res.message || 'Theme export failed'));
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
        })
        .always(function () {
            // Re-enable button after request completes
            $btn.prop('disabled', false);
            $btn.html('📦 Export Theme');
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
});

